<div class="text-center py-5">
    <i class="bi {{ $icon ?? 'bi-inbox' }}
        fs-1 text-muted">
    </i>
    <div class="mt-3 fw-semibold">
        {{ $title }}
    </div>
    @if(isset($description))
    <small class="text-muted">
        {{ $description }}
    </small>
    @endif
</div>
