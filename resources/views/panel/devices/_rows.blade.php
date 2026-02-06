@forelse($rows as $row)
    @php
        /** @var \App\Models\EwelinkDevice $device */
        $device = $row['device'];
        $snapshot = $row['snapshot'];
    @endphp
    <tr>
        <td class="text-break">{{ $device->device_id }}</td>
        <td>
            <div>{{ $device->name }}</div>
            @if($device->description)
                <small class="text-muted">{{ $device->description }}</small>
            @endif
        </td>
        <td>{{ $device->device_type }}</td>
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
            @if (!empty($snapshot['switch_states']))
                @foreach ($snapshot['switch_states'] as $channel => $state)
                    @php $stateValue = strtolower((string) $state); @endphp
                    <span class="me-2">
                        <span class="text-muted">ch{{ $channel }}:</span>
                        @if ($stateValue === 'on')
                            <span class="fw-bold text-success">ON</span>
                        @elseif ($stateValue === 'off')
                            <span class="fw-bold text-danger">OFF</span>
                        @else
                            <span class="fw-bold text-warning">{{ strtoupper($stateValue) }}</span>
                        @endif
                    </span>
                @endforeach
            @else
                {{ $snapshot['switches'] }}
            @endif
        </td>
        <td>{{ $snapshot['target_temperature'] }}</td>
        <td class="text-break">
            @if (!empty($snapshot['schedule_lines']))
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
    </tr>
@empty
    <tr>
        <td colspan="11" class="text-center text-muted">Brak skonfigurowanych urzadzen. Dodaj je w Ustawieniach portalu.</td>
    </tr>
@endforelse
