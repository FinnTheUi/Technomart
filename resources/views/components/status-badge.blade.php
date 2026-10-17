@props(['status'])
{{-- Renders any status enum (App\Enums\*) as a coloured badge. --}}
<span {{ $attributes->merge(['class' => 'badge '.$status->badge()]) }}>{{ $status->label() }}</span>
