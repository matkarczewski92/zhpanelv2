@forelse($rows as $row)
    @php
        /** @var \App\Models\EwelinkDevice $device */
        $device = $row['device'];
        $snapshot = $row['snapshot'];
        $scheduleSeedJson = json_encode(
            $snapshot['schedule_editor'] ?? ['kind' => 'switch_window', 'on_time' => '09:00', 'off_time' => '21:00', 'days' => [0, 1, 2, 3, 4, 5, 6]],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
        $scheduleSeedBase64 = base64_encode($scheduleSeedJson !== false ? $scheduleSeedJson : '{}');
    @endphp
    <tr>
        <td class="text-break">{{ $device->device_id }}</td>
        <td>
            <div>{{ $device->name }}</div>
            @if($device->description)
                <small class="text-muted">{{ $device->description }}</small>
            @endif
        </td>
        <td>
            @php
                $deviceType = strtolower((string) $device->device_type);
                $deviceTypeLabel = match ($deviceType) {
                    'thermostat' => 'T',
                    'switch' => 'P',
                    'thermostat_hygrostat' => 'T+H',
                    default => strtoupper((string) $device->device_type),
                };
            @endphp
            {{ $deviceTypeLabel }}
        </td>
        <td>{{ $snapshot['online'] }}</td>
        <td>{{ $snapshot['temperature'] }}</td>
        <td>{{ $snapshot['humidity'] }}</td>
        <td>
            @php $switchState = strtolower((string) $snapshot['switch']); @endphp
            @if ($switchState === 'on')
                <span class="fw-bold text-success">ON</span>
            @elseif ($switchState === 'off')
                <span class="fw-bold text-danger">OFF</span>
            @elseif ($switchState === 'mixed')
                <span class="fw-bold text-warning">MIXED</span>
            @else
                <span class="text-muted">{{ $snapshot['switch'] }}</span>
            @endif
        </td>
        <td class="text-break">
            @if (!empty($snapshot['auto_control_view']['lines'] ?? []))
                <div class="small fw-semibold">{{ $snapshot['auto_control_view']['mode_line'] }}</div>
                @foreach (($snapshot['auto_control_view']['lines'] ?? []) as $line)
                    <div class="small">{{ $line }}</div>
                @endforeach
            @elseif (!empty($snapshot['schedule_lines']))
                @foreach (array_slice($snapshot['schedule_lines'], 0, 4) as $line)
                    <div class="small">{{ $line }}</div>
                @endforeach
                @if (count($snapshot['schedule_lines']) > 4)
                    <div class="small text-muted">+{{ count($snapshot['schedule_lines']) - 4 }} wiecej</div>
                @endif
            @else
                {{ $snapshot['schedule'] }}
            @endif
        </td>
        <td>
            {{ $device->last_synced_at?->format('Y-m-d H:i:s') ?? '-' }}
            @if($device->last_error)
                <div class="small text-warning mt-1">{{ $device->last_error }}</div>
            @endif
        </td>
        <td class="text-nowrap">
            <div class="btn-group btn-group-sm mb-1" role="group" aria-label="Sterowanie urzadzeniem">
                <button
                    type="button"
                    class="btn btn-outline-success"
                    data-device-toggle
                    data-url="{{ route('panel.devices.toggle', $device) }}"
                    data-state="on"
                >
                    Wlacz
                </button>
                <button
                    type="button"
                    class="btn btn-outline-danger"
                    data-device-toggle
                    data-url="{{ route('panel.devices.toggle', $device) }}"
                    data-state="off"
                >
                    Wylacz
                </button>
            </div>
            <div>
                <button
                    type="button"
                    class="btn btn-outline-info btn-sm"
                    data-device-schedule
                    data-url="{{ route('panel.devices.schedule', $device) }}"
                    data-schedule="{{ $scheduleSeedBase64 }}"
                    data-device-name="{{ $device->name }}"
                    data-device-type="{{ $device->device_type }}"
                >
                    Edytuj harmonogram
                </button>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="10" class="text-center text-muted">Brak skonfigurowanych urzadzen. Dodaj je w Ustawieniach portalu.</td>
    </tr>
@endforelse
