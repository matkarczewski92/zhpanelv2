<?php

namespace App\Application\Animals\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageOptimizationService
{
    private ImageManager $manager;
    private int $maxLongEdge;
    private int $jpegQuality;
    private bool $preferWebp;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
        $config = config('image_uploads', []);
        $this->maxLongEdge = (int) ($config['max_long_edge'] ?? 1920);
        $this->jpegQuality = (int) ($config['jpeg_quality'] ?? 80);
        $this->preferWebp = (bool) ($config['output_prefer_webp'] ?? false);
    }

    /**
     * @return array{path:string,url:string}
     */
    public function optimizeAndStore(UploadedFile $file, string $targetDir = 'Image'): array
    {
        $image = $this->manager->read($file->getRealPath());

        // Resize to max 1920x1080 (no upscaling)
        $image->scaleDown(1920, 1080);

        $format = 'jpeg';
        $quality = 85;

        // Strip metadata
        if (method_exists($image, 'strip')) {
            $image->strip();
        }

        $filename = $this->buildFilename($format);
        $relativePath = trim($targetDir, '/') . '/' . $filename;

        $encoded = $image->encodeByExtension($format, $quality)->toString();

        \Storage::disk('public')->put($relativePath, $encoded);

        return [
            'path' => 'storage/' . $relativePath,
            'url' => asset('storage/' . $relativePath),
        ];
    }

    /**
     * Stores optimized image directly inside /public/{targetDir}.
     *
     * @return array{path:string,url:string}
     */
    public function optimizeAndStoreInPublicPath(UploadedFile $file, string $targetDir = 'Image'): array
    {
        $image = $this->manager->read($file->getRealPath());

        // Resize to max 1920x1080 (no upscaling)
        $image->scaleDown(1920, 1080);

        $format = 'jpeg';
        $quality = 85;

        if (method_exists($image, 'strip')) {
            $image->strip();
        }

        $filename = $this->buildFilename($format);
        $cleanDir = trim($targetDir, '/');
        $directory = public_path($cleanDir);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $relativePath = $cleanDir . '/' . $filename;
        $fullPath = public_path($relativePath);
        $encoded = $image->encodeByExtension($format, $quality)->toString();
        file_put_contents($fullPath, $encoded);

        return [
            'path' => $relativePath,
            'url' => asset($relativePath),
        ];
    }

    private function buildFilename(string $format): string
    {
        return uniqid('img_', true) . '.' . $format;
    }

    private function determineFormat(string $mime, bool $hasAlpha): string
    {
        if ($this->preferWebp) {
            return 'webp';
        }

        $mime = strtolower($mime);
        return match (true) {
            str_contains($mime, 'png') && $hasAlpha => 'png',
            default => 'jpeg',
        };
    }
}
