<?php

namespace App\Http\Controllers\Web\Todo;

use App\Enums\Todo\TodoPriority;
use App\Enums\Todo\TodoStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\TodoRequest;
use App\Models\ActivityLog;
use App\Models\Todo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class TodoController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {

            $data = Todo::query()->where('user_id', auth()->id())->smartOrder();

            if ($request->filled('search_title'))
                $data->where('title', 'like', '%' . $request->search_title . '%');

            if ($request->filled('status'))
                $data->where('status', $request->status);

            if ($request->filled('priority'))
                $data->where('priority', $request->priority);

            return DataTables::of($data)
                ->addColumn('status_badge', function ($row) {
                    return '<button class="btn btn-sm toggleCompleteBtn ' . ($row->status == TodoStatus::Done ? 'btn-dark' : 'btn-outline-dark') . '"
                            data-id="' . $row->id . '">
                        <i class="bi bi-check2 me-2"></i>
                    </button>';
                })
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="todo-checkbox" value="' . $row->id . '">';
                })
                ->addColumn('priority_badge', function ($row) {
                    return '<span class="badge ' . $row->priority->badge() . '">' . $row->priority->label() . '</span>';
                })
                ->addColumn('action', function ($row) {
                    // <a href="' . route('todo.edit', $row->id) . '"
                    //     class="btn btn-sm btn-outline-dark">
                    //     <i class="bi bi-pencil-square me-2"></i>
                    // </a>
                    return '
                        <div class="d-flex justify-content-center gap-2">
                            <button class="btn btn-sm btn-outline-dark editTodoBtn" data-id=' . $row->id . '>
                                <i class="bi bi-pencil-square me-2"></i>
                            </button>
                            <form method="POST"
                                action="' . route('todo.destroy', $row->id) . '"
                                onsubmit="return confirm(\'Archive this todo?\')">
                                ' . csrf_field() . '
                                ' . method_field('DELETE') . '
                                <button type="submit"
                                    class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-archive me-2"></i>
                                </button>
                            </form>
                        </div>
                    ';
                })->addColumn('status_action', function ($row) {
                    $html = '<select class="form-select form-select-sm todo-status" data-id="' . $row->id . '">';
                    foreach (TodoStatus::cases() as $status) {
                        $selected = $row->status->value == $status->value ? 'selected' : '';
                        $html .= '<option value="' . $status->value . '"' . $selected . '>' . $status->label() . '</option>';
                    }
                    $html .= '</select>';
                    return $html;
                })->addColumn('priority_action', function ($row) {
                    $html = '<select class="form-select form-select-sm inlinePriority" data-id="' . $todo->id . '">';
                    foreach (TodoPriority::cases() as $priority) {
                        $selected = $row->priority->value == $priority->value ? 'selected' : '';
                        $html .= '<option value="' . $priority->value . '"' . $selected . '>' . $priority->label() . '</option>';
                    }
                    $html .= '</select>';
                    return $html;
                })->addColumn('due_status', function ($row) {
                    return '<div class="d-flex flex-column">
                        <span class="badge ' . $row->dueBadge() . '">
                            ' . ucfirst($row->dueStatus()) . '
                        </span>
                        <small class="text-muted mt-1">
                            ' . $row->dueLabel() . '
                        </small>
                    </div>';
                })->addColumn('due_action', function ($row) {
                    return '<input type="date" class="form-control form-control-sm inlineDueDate" value="' . $row->due_date . '" data-id="' . $row->id . '">';
                })->rawColumns(['status_badge', 'status_action', 'priority_badge', 'action', 'checkbox', 'due_status', 'due_action'])->make(true);
        }

        $statusOption = TodoStatus::cases();
        $priorityOption = TodoPriority::cases();

        return view('todo.index', compact('statusOption', 'priorityOption'));
    }

    public function create()
    {
        $statusOption = TodoStatus::cases();
        $priorityOption = TodoPriority::cases();

        return view('todo.create', compact('statusOption', 'priorityOption'));
    }

    public function store(TodoRequest $request)
    {
        $todo = auth()->user()->todos()->create($request->validated());

        ActivityLog::add(
            'created',
            $todo,
            'Created a new todo'
        );

        return redirect()
            ->route('todo.index')
            ->with('success', 'Todo created successfully.');
    }

    public function edit($id)
    {
        $todo = auth()->user()->todos()->findOrFail($id);

        $statusOption = TodoStatus::cases();
        $priorityOption = TodoPriority::cases();

        return view('todo.edit', compact('todo', 'statusOption', 'priorityOption'));
    }

    public function update(TodoRequest $request, $id)
    {
        $todo = auth()->user()->todos()->findOrFail($id);

        $todo->update($request->validated());
        ActivityLog::add(
            'update',
            $todo,
            'Updated todo'
        );

        return redirect()
            ->route('todo.index')
            ->with('success', 'Todo updated successfully.');
    }

    public function destroy($id)
    {
        $todo = auth()->user()
            ->todos()
            ->findOrFail($id);

        ActivityLog::add(
            'archived',
            $todo,
            'Archived a todo'
        );

        $todo->delete();

        return redirect()
            ->route('todo.index')
            ->with('success', 'Todo archived successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required'],
        ]);

        $todo = auth()->user()
            ->todos()
            ->findOrFail($id);

        $todo->update([
            'status' => $validated['status'],
            'completed_at' => TodoStatus::Done->value == $validated['status'] ? Carbon::now()->format('Y-m-d H:i:s') : NULL
        ]);

        ActivityLog::add(
            'status_updated',
            $todo,
            'Updated todo status'
        );

        return response()->json([
            'success' => true
        ]);
    }

    public function archive()
    {
        $todos = auth()->user()
            ->todos()
            ->onlyTrashed()
            ->latest()
            ->get();

        return view('todo.archive', compact('todos'));
    }

    public function restore($id)
    {
        $todo = Todo::onlyTrashed()
            ->where('user_id', auth()->id())
            ->findOrFail($id);

        $todo->restore();

        ActivityLog::add(
            'restored',
            $todo,
            'Restored archived todo'
        );

        return redirect()
            ->route('todo.archive')
            ->with('success', 'Todo restored.');
    }

    public function bulkDone(Request $request)
    {
        Todo::whereIn('id', $request->ids)
            ->where('user_id', auth()->id())
            ->update(['status' => TodoStatus::Done]);

        return response()->json([
            'success' => true
        ]);
    }

    public function bulkArchive(Request $request)
    {
        Todo::whereIn('id', $request->ids)
            ->where('user_id', auth()->id())
            ->delete();

        return response()->json([
            'success' => true
        ]);
    }

    public function toggleComplete($id)
    {
        $todo = auth()->user()
            ->todos()
            ->findOrFail($id);

        abort_if($todo->user_id !== auth()->id(), 403);

        $todo->status = $todo->status === TodoStatus::Done ? TodoStatus::Pending : TodoStatus::Done;
        $todo->save();
        ActivityLog::add('status_updated', $todo, 'Updated completion status');

        return response()->json([
            'success' => true
        ]);
    }
    public function updatePriority(Request $request, $id)
    {
        $todo = auth()->user()
            ->todos()
            ->findOrFail($id);

        abort_if($todo->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'priority' => ['required']
        ]);

        $todo->update([
            'priority' => $validated['priority']
        ]);

        return response()->json([
            'success' => true
        ]);
    }
    public function updateDueDate(Request $request, $id)
    {
        $todo = auth()->user()
            ->todos()
            ->findOrFail($id);

        abort_if($todo->user_id !== auth()->id(), 403);

        $validated = $request->validate([
            'due_date' => [
                'nullable',
                'date'
            ]
        ]);

        $todo->update([
            'due_date' =>
            $validated['due_date']
        ]);

        return response()->json([
            'success' => true
        ]);
    }
}
