{{-- One floor-plan tile. @param int $floor  @param int $num  @param \App\Models\Stall|null $s --}}
<div class="fp-stall {{ $s ? $s->status->value : 'empty' }}"
     data-floor="{{ $floor }}" data-num="{{ $num }}"
     @if($s) data-stall-id="{{ $s->id }}" data-stall='@json($s->toPublicArray())' title="{{ $s->name }} — {{ $s->status->label() }}" @endif
     onclick="fpClick(event)">
    <span class="fp-bubble" aria-hidden="true"></span>
    <div class="fp-num">{{ $num }}</div>
    <div class="fp-name">{{ $s->name ?? '' }}</div>
</div>
