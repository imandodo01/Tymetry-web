@extends('layout.app')

@section('title', 'Archived Todo')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/datatables/datatables.min.css') }}">
@endpush

@section('content')
@if (session('success'))
<div class="alert alert-light alert-dismissible fade show shadow-sm border" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-semibold mb-1">
            Archived Todos
        </h4>
        <small class="text-muted">
            Archived tasks can be restored later
        </small>
    </div>
    <a href="{{ route('todo.index') }}" class="btn btn-outline-dark btn-sm">
        <i class="bi bi-arrow-left me-1"></i>
        Back to Todo
    </a>
</div>
<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <div class="d-flex gap-2 mb-3">
            <button class="btn btn-outline-dark btn-sm" id="bulkRestore">
                Restore
            </button>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th width="40" class="px-4 py-3">
                            <input type="checkbox" id="checkAll" onclick="event.stopPropagation();">
                        </th>
                        <th class="px-4 py-3">
                            Title
                        </th>
                        <th>
                            Status
                        </th>
                        <th>
                            Priority
                        </th>
                        <th>
                            Archived At
                        </th>
                        <th class="text-center">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($todos as $todo)
                    <tr>
                        <td class="px-4 py-3">
                            <input type="checkbox" class="todo-checkbox" value="{{ $todo->id }}">
                        </td>
                        <td class="px-4 py-3">
                            <div class="fw-semibold">
                                {{ $todo->title }}
                            </div>
                            @if ($todo->description)
                            <small class="text-muted">
                                {{ Str::limit($todo->description, 60) }}
                            </small>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $todo->status->badge() }}">
                                {{ $todo->status->label() }}
                            </span>
                        </td>
                        <td>
                            <span class="badge {{ $todo->priority->badge() }}">
                                {{ $todo->priority->label() }}
                            </span>
                        </td>
                        <td>
                            <small class="text-muted">
                                {{ $todo->deleted_at->diffForHumans() }}
                            </small>
                        </td>
                        <td class="text-center">
                            <form method="POST" action="{{ route('todo.restore', $todo->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-dark">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>
                                    Restore
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">
                            <x-empty-state icon="bi-archive" title="No archived todo"
                                description="Archived tasks will appear here." />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/datatables/datatables.min.js') }}"></script>
<script src="{{ asset('assets/js/archive.js') }}"></script>
@endpush