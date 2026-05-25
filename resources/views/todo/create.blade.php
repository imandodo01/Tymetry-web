{{-- @extends('layout.app')

@section('title', 'Todo')

@section('content') --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 px-4">
        <h5 class="mb-0 fw-semibold">
            Create Todo
        </h5>
    </div>

    <div class="card-body p-4">
        <form method="POST" action="{{ route('todo.store') }}">
            @csrf
            @include('todo._form')

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('todo.index') }}" class="btn btn-light border">Cancel</a>
                <button type="submit" class="btn btn-dark">Save Todo</button>
            </div>
        </form>
    </div>
</div>
{{-- @endsection --}}
