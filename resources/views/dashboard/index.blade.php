@extends('layout.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
    <div>
        <p class="text-muted small mb-1">{{ now()->format('l, d F Y') }}</p>
        <h2 class="h4 fw-semibold mb-1">Your day at a glance</h2>
        <p class="text-muted small mb-0">A clear view of what needs your attention.</p>
    </div>
    <a href="{{ route('todo.index') }}" class="btn btn-outline-dark btn-sm">
        View all tasks <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
    </a>
</div>

<section aria-label="Task overview" class="row g-3 mb-4">
    @foreach ($cards as $key => $label)
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 p-lg-4">
                <p class="text-muted small mb-2">{{ $label }}</p>
                <p class="h3 fw-semibold mb-0">{{ ${$key} }}</p>
            </div>
        </div>
    </div>
    @endforeach
</section>

<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <section class="card border-0 shadow-sm h-100" aria-labelledby="overdue-heading">
            <div class="card-header bg-white border-0 px-4 pt-4 pb-2 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="h6 fw-semibold mb-1" id="overdue-heading">Overdue</h3>
                    <p class="text-muted small mb-0">Tasks that need attention</p>
                </div>
                <span class="badge text-bg-danger">{{ $overdueTodos->count() }}</span>
            </div>
            <div class="card-body px-4 pt-2">
                @forelse ($overdueTodos as $todo)
                <div class="d-flex justify-content-between align-items-start gap-3 py-3 border-bottom">
                    <div class="min-w-0">
                        <p class="fw-semibold mb-1 text-break">{{ $todo->title }}</p>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge {{ $todo->status->badge() }}">{{ $todo->status->label() }}</span>
                            <span class="small text-muted">{{ $todo->priority->label() }} priority</span>
                        </div>
                    </div>
                    <span class="small text-danger text-nowrap">{{ \Carbon\Carbon::parse($todo->due_date)->diffForHumans() }}</span>
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="bi bi-check2-circle fs-4 d-block mb-2" aria-hidden="true"></i>
                    <p class="small mb-0">You're all caught up. No overdue tasks.</p>
                </div>
                @endforelse
            </div>
        </section>
    </div>

    <div class="col-xl-6">
        <section class="card border-0 shadow-sm h-100" aria-labelledby="today-heading">
            <div class="card-header bg-white border-0 px-4 pt-4 pb-2 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="h6 fw-semibold mb-1" id="today-heading">Due Today</h3>
                    <p class="text-muted small mb-0">Open tasks scheduled for today</p>
                </div>
                <span class="badge text-bg-dark">{{ $todayTodos->count() }}</span>
            </div>
            <div class="card-body px-4 pt-2">
                @forelse ($todayTodos as $todo)
                <div class="d-flex justify-content-between align-items-start gap-3 py-3 border-bottom">
                    <div class="min-w-0">
                        <p class="fw-semibold mb-1 text-break">{{ $todo->title }}</p>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge {{ $todo->status->badge() }}">{{ $todo->status->label() }}</span>
                            <span class="small text-muted">{{ $todo->priority->label() }} priority</span>
                        </div>
                    </div>
                    @if ($todo->due_time)
                    <span class="small text-muted text-nowrap">{{ \Carbon\Carbon::parse($todo->due_time)->format('g:i A') }}</span>
                    @else
                    <span class="small text-muted text-nowrap">Today</span>
                    @endif
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="bi bi-calendar-check fs-4 d-block mb-2" aria-hidden="true"></i>
                    <p class="small mb-0">No open tasks due today.</p>
                </div>
                @endforelse
            </div>
        </section>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7">
        <section class="card border-0 shadow-sm h-100" aria-labelledby="upcoming-heading">
            <div class="card-header bg-white border-0 px-4 pt-4 pb-2 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="h6 fw-semibold mb-1" id="upcoming-heading">Upcoming</h3>
                    <p class="text-muted small mb-0">What's coming next</p>
                </div>
                <span class="badge text-bg-secondary">{{ $upcomingTodos->count() }}</span>
            </div>
            <div class="card-body px-4 pt-2">
                @forelse ($upcomingTodos as $todo)
                <div class="d-flex justify-content-between align-items-start gap-3 py-3 border-bottom">
                    <div class="min-w-0">
                        <p class="fw-semibold mb-1 text-break">{{ $todo->title }}</p>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge {{ $todo->status->badge() }}">{{ $todo->status->label() }}</span>
                            <span class="small text-muted">{{ $todo->priority->label() }} priority</span>
                        </div>
                    </div>
                    <span class="small text-muted text-nowrap">{{ \Carbon\Carbon::parse($todo->due_date)->format('M j') }}</span>
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="bi bi-calendar2-week fs-4 d-block mb-2" aria-hidden="true"></i>
                    <p class="small mb-0">Nothing scheduled after today.</p>
                </div>
                @endforelse
            </div>
        </section>
    </div>

    <div class="col-xl-5">
        <section class="card border-0 shadow-sm h-100" aria-labelledby="activity-heading">
            <div class="card-header bg-white border-0 px-4 pt-4 pb-2">
                <h3 class="h6 fw-semibold mb-1" id="activity-heading">Recent Activity</h3>
                <p class="text-muted small mb-0">Your latest updates</p>
            </div>
            <div class="card-body px-4 pt-2">
                @forelse ($activities as $activity)
                <div class="py-3 border-bottom">
                    <p class="fw-semibold small mb-1">{{ ucfirst(str_replace('_', ' ', $activity->action)) }}</p>
                    <p class="text-muted small mb-1">{{ $activity->description }}</p>
                    <time class="text-muted" style="font-size: 11px;" datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->diffForHumans() }}</time>
                </div>
                @empty
                <div class="text-center text-muted py-4">
                    <i class="bi bi-clock-history fs-4 d-block mb-2" aria-hidden="true"></i>
                    <p class="small mb-0">Your recent activity will appear here.</p>
                </div>
                @endforelse
            </div>
        </section>
    </div>
</div>

<section class="card border-0 shadow-sm" aria-labelledby="status-heading">
    <div class="card-header bg-white border-0 px-4 pt-4 pb-2">
        <h3 class="h6 fw-semibold mb-1" id="status-heading">Task Status</h3>
        <p class="text-muted small mb-0">A summary of your current task distribution</p>
    </div>
    <div class="card-body px-4 pb-4">
        <div style="height: 220px; max-width: 520px; margin: 0 auto;">
            <canvas id="statusChart" role="img" aria-label="Doughnut chart showing pending, in progress, and completed task counts"></canvas>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ asset('assets/vendor/chartjs/chart.umd.min.js') }}"></script>
<script>
    const statusChartElement = document.getElementById('statusChart');

    new Chart(statusChartElement, {
        type: 'doughnut',
        data: {
            labels: ['Pending', 'In Progress', 'Done'],
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
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
</script>
@endpush
