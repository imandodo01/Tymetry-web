@extends('layout.app')

@section('title', 'Todo')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/vendor/datatables/datatables.min.css') }}">
@endpush

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 px-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-1 fw-semibold">
                    Todo List
                </h5>
                <small class="text-muted">
                    Manage your task activity
                </small>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group btn-group-sm" role="group" aria-label="Todo view">
                    <button type="button" class="btn btn-dark" id="showTodoList" aria-pressed="true">List</button>
                    <button type="button" class="btn btn-outline-dark" id="showTodoBoard" aria-pressed="false">Board</button>
                </div>
                <a href="{{ route('todo.create') }}" class="btn btn-dark btn-sm">
                <i class="bi bi-plus-lg me-1"></i>
                Add Todo
            </a>
            </div>
            {{-- <button class="btn btn-outline-dark btn-sm" id="createTodoBtn">Add Todo</button> --}}
        </div>
    </div>

    <div class="card-body p-4">
        <div class="table-responsive">
            <div class="row g-3 mb-4" role="group" aria-label="Task filters">
                <div class="col-md-4">
                    <label class="visually-hidden" for="searchTitle">Search tasks by title</label>
                    <input type="search" id="searchTitle" class="form-control" placeholder="Search title..." autocomplete="off">
                </div>
                <div class="col-md-3">
                    <label class="visually-hidden" for="filterStatus">Filter tasks by status</label>
                    <select id="filterStatus" class="form-select">
                        <option value="">All Status</option>
                        @foreach ($statusOption as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="visually-hidden" for="filterPriority">Filter tasks by priority</label>
                    <select id="filterPriority" class="form-select">
                        <option value=""> All Priority</option>
                        @foreach ($priorityOption as $priority)
                        <option value="{{ $priority->value }}">{{ $priority->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="resetTodoFilters">
                        Clear filters
                    </button>
                </div>
            </div>
            <div id="todoListView">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3" role="group" aria-label="Bulk task actions">
                <button type="button" class="btn btn-outline-dark btn-sm" id="bulkDone" disabled>
                    Mark Done
                </button>
                <button type="button" class="btn btn-outline-dark btn-sm" id="bulkArchive" disabled>
                    Archive
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="exportSelectedTodos" data-export-url="{{ route('todo.export') }}" disabled>
                    Export selected
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="exportAllTodos">
                    Export all active
                </button>
                <small class="text-muted ms-auto" id="selectedTodoCount" aria-live="polite">0 selected</small>
            </div>
            <div id="todoTableLoading" class="todo-table-loading d-none" role="status" aria-live="polite">
                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                <span>Loading tasks...</span>
            </div>
            <div id="todoTableError" class="todo-table-error d-none" role="alert">
                <div class="fw-semibold mb-1">Unable to load tasks</div>
                <p class="text-muted mb-3">Check your connection and try again. Your current filters will be kept.</p>
                <button type="button" class="btn btn-outline-dark btn-sm" id="retryTodoTable">
                    <i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>
                    Try again
                </button>
            </div>
            <table class="table align-middle mb-0" id="todoTable">
                <thead>
                    <tr class="border-bottom">
                        <th scope="col" width="40">
                            <input type="checkbox" id="checkAll" aria-label="Select all tasks on this page" title="Select all tasks on this page">
                        </th>
                        <th scope="col" class="text-muted fw-semibold py-3">
                            Title
                        </th>
                        <th scope="col" class="text-muted fw-semibold py-3">
                            Completion
                        </th>
                        <th scope="col" class="text-muted fw-semibold py-3">
                            Priority
                        </th>
                        <th scope="col" class="text-muted fw-semibold py-3">
                            Due Status
                        </th>
                        <th scope="col" class="text-muted fw-semibold py-3">
                            Change Status
                        </th>
                        <th scope="col" class="text-muted fw-semibold py-3">
                            Due Date
                        </th>
                        <th scope="col" width="120" class="text-muted fw-semibold py-3 text-center">
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
            </div>
        </div>
        <section id="todoBoardView" class="d-none" aria-label="Todo board">
            <div id="todoBoardLoading" class="todo-table-loading d-none" role="status" aria-live="polite">
                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                <span>Loading board...</span>
            </div>
            <div id="todoBoardError" class="todo-table-error d-none" role="alert">
                <div class="fw-semibold mb-1">Unable to load board</div>
                <p class="text-muted mb-3">Check your connection and try again.</p>
                <button type="button" class="btn btn-outline-dark btn-sm" id="retryTodoBoard">Try again</button>
            </div>
            <div id="todoBoardEmpty" class="text-center text-muted py-5 d-none">
                <i class="bi bi-kanban fs-1" aria-hidden="true"></i>
                <p class="mt-3 mb-0">No tasks match these filters.</p>
            </div>
            <div class="todo-board-grid" id="todoBoardColumns">
                @foreach ($statusOption as $status)
                <section class="todo-board-column" data-status="{{ $status->value }}" aria-labelledby="todo-board-heading-{{ $status->value }}">
                    <header class="todo-board-column-header">
                        <h6 class="mb-0" id="todo-board-heading-{{ $status->value }}">{{ $status->label() }}</h6>
                        <span class="badge text-bg-light" data-column-count="{{ $status->value }}">0</span>
                    </header>
                    <div class="todo-board-cards" data-column-cards="{{ $status->value }}" aria-live="polite"></div>
                </section>
                @endforeach
            </div>
        </section>
    </div>
</div>

<div class="modal fade" id="todoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title">
                    Todo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="todoModalBody">
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/datatables/datatables.min.js') }}"></script>
<script src="{{ asset('assets/js/todo.js') }}"></script>
<script src="{{ asset('assets/js/tymx-export.js') }}"></script>
@endpush