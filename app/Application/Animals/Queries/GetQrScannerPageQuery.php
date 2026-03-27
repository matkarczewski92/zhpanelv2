<?php

namespace App\Application\Animals\Queries;

class GetQrScannerPageQuery
{
    public function handle(): array
    {
        $modes = [
            [
                'key' => 'feeding',
                'label' => 'Karmienie',
                'description' => 'Skan od razu zapisuje karmienie domyslna karma i iloscia 1.',
                'manual_action_label' => 'Dodaj karmienie',
            ],
            [
                'key' => 'weight',
                'label' => 'Waga',
                'description' => 'Po skanie otwiera sie szybkie pole do wpisania wagi i skaner jest wstrzymany.',
                'manual_action_label' => 'Przejdz do wagi',
            ],
            [
                'key' => 'molt',
                'label' => 'Wylinka',
                'description' => 'Skan od razu zapisuje wylinke z aktualna data i godzina.',
                'manual_action_label' => 'Dodaj wylinke',
            ],
        ];

        return [
            'title' => 'Skaner QR',
            'subtitle' => 'Szybkie dodawanie karmienia, wagi i wylinki bez opuszczania panelu.',
            'modes' => $modes,
            'config' => [
                'default_mode' => 'feeding',
                'cooldown_ms' => 2500,
                'modes' => collect($modes)->keyBy('key')->all(),
                'endpoints' => [
                    'resolve' => route('panel.qr-scanner.resolve'),
                    'feeding' => route('panel.qr-scanner.feedings.store'),
                    'weight' => route('panel.qr-scanner.weights.store'),
                    'molt' => route('panel.qr-scanner.molts.store'),
                    'session_summary' => route('panel.qr-scanner.session-summary.store'),
                ],
            ],
        ];
    }
}
