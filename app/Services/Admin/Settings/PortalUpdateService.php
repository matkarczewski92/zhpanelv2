<?php

namespace App\Services\Admin\Settings;

use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class PortalUpdateService
{
    private const UPDATE_LOCK_KEY = 'portal-update:run-lock';
    private const UPDATE_LOCK_SECONDS = 1800;

    public function getPanelData(?array $lastCheck = null, ?array $lastRun = null): array
    {
        $currentSha = $this->safeGitOutput(['git', 'rev-parse', 'HEAD']);
        $remoteUrl = $this->safeGitOutput(['git', 'remote', 'get-url', $this->remote()]);

        return [
            'enabled' => $this->enabled(),
            'process_available' => $this->processAvailable(),
            'git_available' => $this->isGitRepository(),
            'remote' => $this->remote(),
            'branch' => $this->branch(),
            'remote_url' => $remoteUrl,
            'github_repo' => $this->resolveGithubRepository($remoteUrl),
            'local_sha' => $currentSha,
            'local_sha_short' => $this->shortSha($currentSha),
            'last_check' => $lastCheck,
            'last_run' => $lastRun,
            'log_tail' => $this->readLogTail(),
            'log_path' => $this->logPath(),
        ];
    }

    public function checkForUpdates(): array
    {
        $this->assertCanUseUpdater();

        $this->runOrFail(
            ['git', 'fetch', $this->remote(), $this->branch(), '--quiet'],
            'Nie udalo sie pobrac danych z repozytorium.',
            180
        );

        $localSha = $this->requireGitOutput(['git', 'rev-parse', 'HEAD'], 'Brak lokalnego commita.');
        $remoteRef = sprintf('%s/%s', $this->remote(), $this->branch());
        $remoteSha = $this->requireGitOutput(['git', 'rev-parse', $remoteRef], 'Brak commita zdalnego.');
        [$ahead, $behind] = $this->readAheadBehind($remoteRef);

        return [
            'checked_at' => now()->toIso8601String(),
            'local_sha' => $localSha,
            'local_sha_short' => $this->shortSha($localSha),
            'remote_sha' => $remoteSha,
            'remote_sha_short' => $this->shortSha($remoteSha),
            'ahead' => $ahead,
            'behind' => $behind,
            'has_updates' => $behind > 0,
        ];
    }

    public function runUpdate(bool $runMigrate, bool $runBuild): array
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
        if (function_exists('ignore_user_abort')) {
            @ignore_user_abort(true);
        }

        $this->assertCanUseUpdater();
        $this->assertCleanWorkTree();

        $lock = Cache::lock(self::UPDATE_LOCK_KEY, self::UPDATE_LOCK_SECONDS);
        if (!$lock->get()) {
            throw new RuntimeException('Aktualizacja juz trwa. Sprobuj ponownie za chwile.');
        }

        $startedAt = now();
        $beforeSha = $this->safeGitOutput(['git', 'rev-parse', 'HEAD']);
        $steps = [];
        $enteredMaintenance = false;
        $success = true;
        $errorMessage = null;

        try {
            foreach ($this->buildUpdateSteps($runMigrate, $runBuild) as $step) {
                $result = $this->executeStep(
                    label: $step['label'],
                    command: $step['command'],
                    timeoutSeconds: $step['timeout']
                );

                $steps[] = $result;

                if (($step['step'] ?? null) === 'maintenance_down' && $result['success']) {
                    $enteredMaintenance = true;
                }

                if (!$result['success']) {
                    $success = false;
                    $errorMessage = sprintf('%s (exit code: %d)', $step['label'], $result['exit_code']);
                    break;
                }
            }
        } finally {
            if ($enteredMaintenance) {
                $maintenanceUpResult = $this->executeStep(
                    label: 'Wylaczenie maintenance mode',
                    command: $this->artisanCommand(['up']),
                    timeoutSeconds: 120
                );
                $steps[] = $maintenanceUpResult;

                if (!$maintenanceUpResult['success'] && $success) {
                    $success = false;
                    $errorMessage = 'Nie udalo sie wylaczyc maintenance mode.';
                }
            }

            $this->releaseLock($lock);
        }

        $finishedAt = now();
        $afterSha = $this->safeGitOutput(['git', 'rev-parse', 'HEAD']);

        $summary = [
            'started_at' => $startedAt->toIso8601String(),
            'finished_at' => $finishedAt->toIso8601String(),
            'success' => $success,
            'error' => $errorMessage,
            'run_migrate' => $runMigrate,
            'run_build' => $runBuild,
            'before_sha' => $beforeSha,
            'before_sha_short' => $this->shortSha($beforeSha),
            'after_sha' => $afterSha,
            'after_sha_short' => $this->shortSha($afterSha),
            'updated' => is_string($beforeSha) && is_string($afterSha) && $beforeSha !== $afterSha,
            'steps_count' => count($steps),
            'log_path' => $this->logPath(),
        ];

        $this->appendRunLog($summary, $steps);

        return $summary;
    }

    private function enabled(): bool
    {
        return (bool) config('services.portal_update.enabled', false);
    }

    private function remote(): string
    {
        return trim((string) config('services.portal_update.remote', 'origin'));
    }

    private function branch(): string
    {
        return trim((string) config('services.portal_update.branch', 'main'));
    }

    private function assertCanUseUpdater(): void
    {
        if (!$this->processAvailable()) {
            throw new RuntimeException('Updater wymaga proc_open, ale ta funkcja jest niedostepna w PHP na tym serwerze.');
        }

        if (!$this->enabled()) {
            throw new RuntimeException('Updater jest wylaczony. Ustaw PORTAL_UPDATE_ENABLED=true w .env.');
        }

        if (!$this->isGitRepository()) {
            throw new RuntimeException('Brak repozytorium Git. Aktualizacja jest niedostepna.');
        }
    }

    private function isGitRepository(): bool
    {
        $output = $this->safeGitOutput(['git', 'rev-parse', '--is-inside-work-tree']);

        return $output === 'true';
    }

    private function assertCleanWorkTree(): void
    {
        $status = $this->requireGitOutput(['git', 'status', '--porcelain'], 'Nie udalo sie sprawdzic statusu Git.');
        if (trim($status) !== '') {
            throw new RuntimeException('Repozytorium ma lokalne zmiany. Aktualizacja zostala zablokowana.');
        }
    }

    /**
     * @return array<int, array{step?:string, label:string, command:array<int, string>, timeout:int}>
     */
    private function buildUpdateSteps(bool $runMigrate, bool $runBuild): array
    {
        $remote = $this->remote();
        $branch = $this->branch();

        $steps = [
            [
                'step' => 'maintenance_down',
                'label' => 'Wlaczenie maintenance mode',
                'command' => $this->artisanCommand(['down', '--refresh=15']),
                'timeout' => 120,
            ],
            [
                'label' => 'Pobranie zmian z GitHub',
                'command' => ['git', 'fetch', $remote, $branch],
                'timeout' => 240,
            ],
            [
                'label' => 'Aktualizacja kodu (git pull --ff-only)',
                'command' => ['git', 'pull', '--ff-only', $remote, $branch],
                'timeout' => 300,
            ],
            [
                'label' => 'Instalacja zaleznosci backend (composer install)',
                'command' => $this->composerCommand([
                    'install',
                    '--no-dev',
                    '--optimize-autoloader',
                    '--no-interaction',
                    '--prefer-dist',
                ]),
                'timeout' => 900,
            ],
        ];

        if ($runMigrate) {
            $steps[] = [
                'label' => 'Migracje bazy danych',
                'command' => $this->artisanCommand(['migrate', '--force']),
                'timeout' => 600,
            ];
        }

        if ($runBuild) {
            $steps[] = [
                'label' => 'Instalacja zaleznosci frontend (npm ci)',
                'command' => $this->npmCommand(['ci']),
                'timeout' => 900,
            ];
            $steps[] = [
                'label' => 'Budowanie frontend (npm run build)',
                'command' => $this->npmCommand(['run', 'build']),
                'timeout' => 900,
            ];
        }

        $steps[] = [
            'label' => 'Czyszczenie cache',
            'command' => $this->artisanCommand(['optimize:clear']),
            'timeout' => 300,
        ];
        $steps[] = [
            'label' => 'Budowanie config cache',
            'command' => $this->artisanCommand(['config:cache']),
            'timeout' => 300,
        ];
        $steps[] = [
            'label' => 'Budowanie route cache',
            'command' => $this->artisanCommand(['route:cache']),
            'timeout' => 300,
        ];
        $steps[] = [
            'label' => 'Budowanie view cache',
            'command' => $this->artisanCommand(['view:cache']),
            'timeout' => 300,
        ];
        $steps[] = [
            'label' => 'Restart kolejek',
            'command' => $this->artisanCommand(['queue:restart']),
            'timeout' => 120,
        ];

        return $steps;
    }

    /**
     * @param array<int, string> $command
     * @return array{label:string, command:string, success:bool, exit_code:int, duration_ms:int, output:string}
     */
    private function executeStep(string $label, array $command, int $timeoutSeconds): array
    {
        if (!$this->processAvailable()) {
            return [
                'label' => $label,
                'command' => $this->commandToString($command),
                'success' => false,
                'exit_code' => 1,
                'duration_ms' => 0,
                'output' => 'Brak proc_open w PHP.',
            ];
        }

        $startedAt = microtime(true);
        try {
            $process = new Process($command, base_path());
            $process->setTimeout($timeoutSeconds);
            $process->run();
        } catch (Throwable $exception) {
            return [
                'label' => $label,
                'command' => $this->commandToString($command),
                'success' => false,
                'exit_code' => 1,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'output' => $this->trimOutput($exception->getMessage()),
            ];
        }

        $output = trim($process->getOutput() . $process->getErrorOutput());
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        return [
            'label' => $label,
            'command' => $process->getCommandLine(),
            'success' => $process->isSuccessful(),
            'exit_code' => $process->getExitCode() ?? 1,
            'duration_ms' => $durationMs,
            'output' => $this->trimOutput($output),
        ];
    }

    /**
     * @param array<int, string> $command
     */
    private function runOrFail(array $command, string $errorMessage, int $timeoutSeconds = 120): void
    {
        $result = $this->executeStep($errorMessage, $command, $timeoutSeconds);
        if (!$result['success']) {
            $details = $result['output'] !== '' ? ' ' . $result['output'] : '';
            throw new RuntimeException($errorMessage . $details);
        }
    }

    /**
     * @param array<int, string> $command
     */
    private function requireGitOutput(array $command, string $errorMessage): string
    {
        $output = $this->safeGitOutput($command);
        if (!is_string($output)) {
            throw new RuntimeException($errorMessage);
        }

        return trim($output);
    }

    /**
     * @param array<int, string> $command
     */
    private function safeGitOutput(array $command): ?string
    {
        if (!$this->processAvailable()) {
            return null;
        }

        try {
            $process = new Process($command, base_path());
            $process->setTimeout(30);
            $process->run();
        } catch (Throwable) {
            return null;
        }

        if (!$process->isSuccessful()) {
            return null;
        }

        return trim($process->getOutput());
    }

    private function processAvailable(): bool
    {
        if (!function_exists('proc_open')) {
            return false;
        }

        $disabled = (string) ini_get('disable_functions');
        if ($disabled === '') {
            return true;
        }

        $disabledFunctions = array_map('trim', explode(',', $disabled));

        return !in_array('proc_open', $disabledFunctions, true);
    }

    /**
     * @return array{0:int, 1:int}
     */
    private function readAheadBehind(string $remoteRef): array
    {
        $output = $this->requireGitOutput(
            ['git', 'rev-list', '--left-right', '--count', sprintf('HEAD...%s', $remoteRef)],
            'Nie udalo sie porownac commitow.'
        );

        $parts = preg_split('/\s+/', trim($output));
        if (!is_array($parts) || count($parts) < 2) {
            throw new RuntimeException('Niepoprawny wynik porownania commitow.');
        }

        return [(int) $parts[0], (int) $parts[1]];
    }

    /**
     * @param array<int, string> $args
     * @return array<int, string>
     */
    private function artisanCommand(array $args): array
    {
        return array_merge([PHP_BINARY, 'artisan'], $args);
    }

    /**
     * @param array<int, string> $args
     * @return array<int, string>
     */
    private function composerCommand(array $args): array
    {
        $composerPhar = base_path('composer.phar');
        if (File::exists($composerPhar)) {
            return array_merge([PHP_BINARY, $composerPhar], $args);
        }

        return array_merge(['composer'], $args);
    }

    /**
     * @param array<int, string> $args
     * @return array<int, string>
     */
    private function npmCommand(array $args): array
    {
        $binary = PHP_OS_FAMILY === 'Windows' ? 'npm.cmd' : 'npm';

        return array_merge([$binary], $args);
    }

    private function shortSha(?string $sha): ?string
    {
        if (!is_string($sha) || $sha === '') {
            return null;
        }

        return substr($sha, 0, 7);
    }

    private function resolveGithubRepository(?string $remoteUrl): ?string
    {
        if (!is_string($remoteUrl) || $remoteUrl === '') {
            return null;
        }

        $sanitized = preg_replace('/\.git$/', '', trim($remoteUrl));
        if (!is_string($sanitized)) {
            return null;
        }

        if (preg_match('~github\.com[:/](?<owner>[^/]+)/(?<repo>[^/]+)$~i', $sanitized, $matches) !== 1) {
            return null;
        }

        return sprintf('%s/%s', $matches['owner'], $matches['repo']);
    }

    private function trimOutput(string $output, int $maxLength = 20000): string
    {
        if (strlen($output) <= $maxLength) {
            return $output;
        }

        return substr($output, 0, $maxLength) . PHP_EOL . '...[output truncated]...';
    }

    private function appendRunLog(array $summary, array $steps): void
    {
        $lines = [];
        $lines[] = str_repeat('=', 80);
        $lines[] = sprintf(
            '[%s] %s',
            $summary['finished_at'] ?? now()->toIso8601String(),
            ($summary['success'] ?? false) ? 'SUCCESS' : 'FAILED'
        );
        $lines[] = sprintf(
            'Before: %s | After: %s | Updated: %s',
            $summary['before_sha_short'] ?? '-',
            $summary['after_sha_short'] ?? '-',
            ($summary['updated'] ?? false) ? 'yes' : 'no'
        );
        $lines[] = sprintf(
            'Options: migrate=%s, build=%s',
            ($summary['run_migrate'] ?? false) ? 'yes' : 'no',
            ($summary['run_build'] ?? false) ? 'yes' : 'no'
        );

        if (is_string($summary['error'] ?? null) && $summary['error'] !== '') {
            $lines[] = 'Error: ' . $summary['error'];
        }

        foreach ($steps as $step) {
            $lines[] = str_repeat('-', 80);
            $lines[] = sprintf(
                '%s | %s | exit=%d | %d ms',
                $step['label'] ?? 'step',
                ($step['success'] ?? false) ? 'OK' : 'FAIL',
                $step['exit_code'] ?? 1,
                $step['duration_ms'] ?? 0
            );
            $lines[] = 'Command: ' . ($step['command'] ?? '');
            $output = trim((string) ($step['output'] ?? ''));
            if ($output !== '') {
                $lines[] = $output;
            }
        }
        $lines[] = '';

        File::append($this->logPath(), implode(PHP_EOL, $lines));
    }

    private function readLogTail(int $maxLines = 140): string
    {
        if (!File::exists($this->logPath())) {
            return '';
        }

        $lines = @file($this->logPath(), FILE_IGNORE_NEW_LINES);
        if (!is_array($lines)) {
            return '';
        }

        return implode(PHP_EOL, array_slice($lines, -$maxLines));
    }

    private function logPath(): string
    {
        return storage_path('logs/portal-update.log');
    }

    private function releaseLock(Lock $lock): void
    {
        try {
            $lock->release();
        } catch (Throwable) {
        }
    }

    /**
     * @param array<int, string> $command
     */
    private function commandToString(array $command): string
    {
        return implode(' ', $command);
    }
}
