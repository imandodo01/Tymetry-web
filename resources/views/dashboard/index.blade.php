@extends('layout.app')

@section('title', 'Dashboard')

@section('content')

<div class="row g-4 mb-4">
    @foreach ($cards as $key => $value)
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-4">
                <small class="text-muted"> {{ $value }} </small>
                <h3 class="fw-bold mt-2 mb-0"> {{ ${$key} }} </h3>
            </div>
        </div>
    </div>
    @endforeach
</div>
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3 px-4">
                <h6 class="mb-0 fw-semibold text-muted">
                    Task Status
                </h6>
            </div>
            <div class="card-body p-4">
                <div style="height: 250px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3 px-4">
                <h5 class="mb-0 fw-semibold">Due Today</h5>
            </div>
            <div class="card-body p-4">
                @forelse ($dueToday as $todo)
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <div class="fw-semibold">{{ $todo->title }}</div>
                        <small class="text-muted">{{ $todo->priority->label() }}</small>
                    </div>
                    <span class="badge {{ $todo->status->badge() }}">{{ $todo->status->label() }}</span>
                </div>
                @empty
                <div class="text-muted text-center py-4">
                    No task due today
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3 px-4">
                <h6 class="mb-0 fw-semibold text-muted">
                    Today Tasks
                </h6>
            </div>
            <div class="card-body p-4">
                @forelse ($todayTodos as $todo)
                <div class="py-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">
                                {{ $todo->title }}
                            </div>
                            <small class="text-muted">
                                Due:
                                {{ $todo->due_date }}
                            </small>
                        </div>
                        <span class="badge {{ $todo->priority->badge() }}">
                            {{ $todo->priority->label() }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="text-muted text-center py-4">
                    No Today task
                </div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3 px-4">
                <h6 class="mb-0 fw-semibold text-muted">
                    Upcoming Tasks
                </h6>
            </div>
            <div class="card-body p-4">
                @forelse ($upcomingTodos as $todo)
                <div class="py-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold">
                                {{ $todo->title }}
                            </div>
                            <small class="text-muted">
                                Due {{ \Carbon\Carbon::parse($todo->due_date)->diffForHumans() }}
                            </small>
                        </div>
                        <span class="badge {{ $todo->priority->badge() }}">
                            {{ $todo->priority->label() }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="text-muted text-center py-4">
                    No Upcoming task
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
<div class="card border-0 mb-4 shadow-sm">
    <div class="card-header bg-white border-0 py-3 px-4">
        <h6 class="mb-0 fw-semibold text-muted">
            Overdue Tasks
        </h6>
    </div>
    <div class="card-body p-4">
        @forelse ($overdueTodos as $todo)
        <div class="py-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">
                        {{ $todo->title }}
                    </div>
                    <small class="text-muted">
                        Due:
                        {{ $todo->due_date }}
                    </small>
                </div>
                <span class="badge bg-dark">
                    Overdue
                </span>
            </div>
        </div>
        @empty
        <div class="text-muted text-center py-4">
            No overdue task
        </div>
        @endforelse
    </div>
</div>
<div class="card border-0 mb-4 shadow-sm">
    <div class="card-header bg-white border-0 py-3 px-4">
        <h6 class="mb-0 fw-semibold text-muted">
            Recent Activity
        </h6>
    </div>
    <div class="card-body p-4">
        @forelse ($activities as $activity)
        <div class="py-3 border-bottom">
            <div class="fw-semibold">
                {{ ucfirst(str_replace('_', ' ', $activity->action)) }}
            </div>
            <small class="text-muted">
                {{ $activity->description }}
                •
                {{ $activity->created_at->diffForHumans() }}
            </small>
        </div>
        @empty
        <div class="text-center text-muted py-4">
            No recent activity
        </div>
        @endforelse
    </div>
</div>

@endsection
@push('scripts')
<script src="{{ asset('assets/vendor/chartjs/chart.umd.min.js') }}"></script>

<script>
    const ctx = document.getElementById('statusChart');

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: [
                'Pending',
                'Progress',
                'Done'
            ],
            datasets: [{
                data: [
                    {{ $statusChart['pending'] }},
                    {{ $statusChart['progress'] }},
                    {{ $statusChart['done'] }}
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>
@endpush
