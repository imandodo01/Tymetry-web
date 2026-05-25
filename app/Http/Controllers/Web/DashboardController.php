<?php

namespace App\Http\Controllers\Web;

use App\Enums\Todo\TodoStatus;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $totalTodo = $user->todos()->count();

        $pendingTodo = $user->todos()
            ->where('status', TodoStatus::Pending)
            ->count();

        $progressTodo = $user->todos()
            ->where('status', TodoStatus::Progress)
            ->count();

        $doneTodo = $user->todos()
            ->where('status', TodoStatus::Done)
            ->count();

        $dueToday = $user->todos()
            ->whereDate('due_date', today())
            ->smartOrder()
            ->take(5)
            ->get();

        $recentTodo = $user->todos()
            ->smartOrder()
            ->take(5)
            ->get();

        $cards = [
            'totalTodo' => 'Total Todo',
            'pendingTodo' => 'Pending Todo',
            'progressTodo' => 'Progress Todo',
            'doneTodo' => 'Done Todo'
        ];

        $statusChart = [
            'pending' => $pendingTodo,
            'progress' => $progressTodo,
            'done' => $doneTodo,
        ];

        $activities = auth()->user()
            ->activityLogs()
            ->latest()
            ->take(10)
            ->get();

        $today = Carbon::today();

        $overdueTodos = auth()->user()
            ->todos()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->where('status', '!=', TodoStatus::Done)
            ->smartOrder()
            ->take(5)
            ->get();

        $todayTodos = auth()->user()
            ->todos()
            ->whereDate('due_date', $today)
            ->where('status', '!=', TodoStatus::Done)
            ->smartOrder()
            ->take(5)
            ->get();

        $upcomingTodos = auth()->user()
            ->todos()
            ->whereDate('due_date', '>', $today)
            ->where('status', '!=', TodoStatus::Done)
            ->orderBy('due_date')
            ->take(5)
            ->get();

        return view('dashboard.index', compact(
            'totalTodo',
            'pendingTodo',
            'progressTodo',
            'doneTodo',
            'dueToday',
            'recentTodo',
            'statusChart',
            'activities',
            'overdueTodos',
            'todayTodos',
            'upcomingTodos',
            'cards'
        ));
    }
}
