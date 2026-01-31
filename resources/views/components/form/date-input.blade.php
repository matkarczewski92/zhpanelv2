@props([
    'name',
    'value' => null,
    'defaultToday' => false,
])

@php
    $oldValue = old($name);
    $formatted = '';

    if ($oldValue !== null) {
        $formatted = $oldValue;
    } elseif ($value instanceof DateTimeInterface) {
        $formatted = $value->format('Y-m-d');
    } elseif (is_string($value) && $value !== '') {
        try {
            $formatted = \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (Exception $e) {
            $formatted = $value;
        }
    } elseif ($defaultToday) {
        $formatted = now()->format('Y-m-d');
    }
@endphp

<input
    type="date"
    name="{{ $name }}"
    value="{{ $formatted }}"
    {{ $attributes->merge(['class' => 'form-control']) }}
/>
