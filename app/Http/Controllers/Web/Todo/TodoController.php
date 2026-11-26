<?php

namespace App\Http\Controllers\Web\Todo;

use App\Enums\Todo\TodoPriority;
use App\Enums\Todo\TodoStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\TodoRequest;
use App\Models\ActivityLog;
use App\Models\Todo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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
                    return '<span class="badge ' . $row->status->badge() . '">' . $row->status->label() . '</span>';
                })
                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="todo-checkbox" value="' . $row->id . '" aria-label="Select task: ' . e($row->title) . '">';
                })
                ->addColumn('priority_badge', function ($row) {
                    return '<span class="badge ' . $row->priority->badge() . '">' . $row->priority->label() . '</span>';
                })
                ->addColumn('action', function ($row) {
                    return '<div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-sm toggleCompleteBtn ' . ($row->status == TodoStatus::Done ? 'btn-dark' : 'btn-outline-dark') . '"
                                data-id="' . $row->id . '"
                                aria-label="' . ($row->status == TodoStatus::Done ? 'Reopen task: ' : 'Mark task complete: ') . e($row->title) . '"
                                title="' . ($row->status == TodoStatus::Done ? 'Reopen task' : 'Mark complete') . '">
                            <i class="bi bi-check2 me-2"></i>
                        </button>
                        <a href="' . route('todo.edit', $row->id) . '"
                            class="btn btn-sm btn-outline-dark" aria-label="Edit task: ' . e($row->title) . '" title="Edit task">
                            <i class="bi bi-pencil-square me-2" aria-hidden="true"></i>
                        </a>
                        <form method="POST"
                            action="' . route('todo.destroy', $row->id) . '"
                            onsubmit="return confirm(\'Archive this todo?\')">
                            ' . csrf_field() . '
                            ' . method_field('DELETE') . '
                            <button type="submit"
                                class="btn btn-sm btn-outline-secondary" aria-label="Archive task: ' . e($row->title) . '" title="Archive task">
                                <i class="bi bi-archive me-2" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>';
                })->addColumn('status_action', function ($row) {
                    $html = '<select class="form-select form-select-sm todo-status" data-id="' . $row->id . '" aria-label="Change status for task: ' . e($row->title) . '">';
                    foreach (TodoStatus::cases() as $status) {
                        $selected = $row->status->value == $status->value ? ' selected' : '';
                        $html .= '<option value="' . $status->value . '"' . $selected . '>' . $status->label() . '</option>';
                    }
                    $html .= '</select>';
                    return $html;
                })->addColumn('priority_action', function ($row) {
                    $html = '<select class="form-select form-select-sm inlinePriority" data-id="' . $row->id . '" aria-label="Change priority for task: ' . e($row->title) . '">';
                    foreach (TodoPriority::cases() as $priority) {
                        $selected = $row->priority->value == $priority->value ? ' selected' : '';
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
                    return '<input type="date" class="form-control form-control-sm inlineDueDate" value="' . $row->due_date . '" data-id="' . $row->id . '" aria-label="Change due date for task: ' . e($row->title) . '">';
                })->rawColumns(['status_badge', 'status_action', 'priority_action', 'priority_badge', 'action', 'checkbox', 'due_status', 'due_action'])->make(true);
        }

        $statusOption = TodoStatus::cases();
        $priorityOption = TodoPriority::cases();

        return view('todo.index', compact('statusOption', 'priorityOption'));
    }

    public function boardData(Request $request)
    {
        $data = Todo::query()
            ->where('user_id', auth()->id())
            ->smartOrder();

        if ($request->filled('search_title')) {
            $data->where('title', 'like', '%' . $request->string('search_title')->toString() . '%');
        }

        if ($request->filled('status')) {
            $data->where('status', $request->input('status'));
        }

        if ($request->filled('priority')) {
            $data->where('priority', $request->input('priority'));
        }

        $todos = $data->get()->map(fn (Todo $todo): array => [
            'id' => $todo->id,
            'title' => $todo->title,
            'status' => $todo->status->value,
            'status_label' => $todo->status->label(),
            'priority' => $todo->priority->value,
            'priority_label' => $todo->priority->label(),
            'priority_badge' => $todo->priority->badge(),
            'due_date' => $todo->due_date
                ? \\Carbon\\Carbon::parse($todo->due_date)->toDateString()
                : null,
            'due_label' => $todo->dueLabel(),
            'due_status' => $todo->dueStatus(),
            'due_badge' => $todo->dueBadge(),
            'edit_url' => route('todo.edit', $todo->id),
        ]);

        return response()->json(['data' => $todos]);
    }

    public function create()
    {
        $statusOption = TodoStatus::cases();
        $priorityOption = TodoPriority::cases();

        return view('todo.create', compact('statusOption', 'priorityOption'));
    }

    public function store(TodoRequest $request)
    {
        $this->mutateWithActivity(
            fn (): Todo => auth()->user()->todos()->create($request->validated()),
            'created',
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
        $this->mutateWithActivity(function () use ($request, $id): Todo {
            $todo = auth()->user()->todos()->findOrFail($id);
            $todo->update($request->validated());

            return $todo;
        }, 'update', 'Updated todo');

        return redirect()
            ->route('todo.index')
            ->with('success', 'Todo updated successfully.');
    }

    public function destroy($id)
    {
        $this->mutateWithActivity(function () use ($id): Todo {
            $todo = auth()->user()->todos()->findOrFail($id);
            $todo->delete();

            return $todo;
        }, 'archived', 'Archived a todo');

        return redirect()
            ->route('todo.index')
            ->with('success', 'Todo archived successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', 'integer', Rule::in(array_map(fn (TodoStatus $status) => $status->value, TodoStatus::cases()))],
        ]);

        $this->mutateWithActivity(function () use ($validated, $id): Todo {
            $todo = auth()->user()->todos()->findOrFail($id);
            $todo->update(['status' => $validated['status']]);

            return $todo;
        }, 'status_updated', 'Updated todo status');

        return response()->json(['success' => true]);
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
        $this->mutateWithActivity(function () use ($id): Todo {
            $todo = Todo::onlyTrashed()
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            $todo->restore();

            return $todo;
        }, 'restored', 'Restored archived todo');

        return redirect()
            ->route('todo.archive')
            ->with('success', 'Todo restored.');
    }

    public function bulkDone(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);

        DB::transaction(function () use ($validated) {
            $todos = Todo::whereIn('id', $validated['ids'])
                ->where('user_id', auth()->id())
                ->get();

            foreach ($todos as $todo) {
                $todo->update([
                    'status' => TodoStatus::Done,
                ]);

                ActivityLog::add('status_updated', $todo, 'Marked todo as done in bulk');
            }
        });

        return response()->json(['success' => true]);
    }

    public function bulkArchive(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);

        DB::transaction(function () use ($validated) {
            $todos = Todo::whereIn('id', $validated['ids'])
                ->where('user_id', auth()->id())
                ->get();

            foreach ($todos as $todo) {
                ActivityLog::add('archived', $todo, 'Archived todo in bulk');
                $todo->delete();
            }
        });

        return response()->json(['success' => true]);
    }

    public function bulkRestore(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);

        DB::transaction(function () use ($validated) {
            $todos = Todo::onlyTrashed()
                ->whereIn('id', $validated['ids'])
                ->where('user_id', auth()->id())
                ->get();

            foreach ($todos as $todo) {
                $todo->restore();

                ActivityLog::add(
                    'restored',
                    $todo,
                    'Restored archived todo'
                );
            }
        });

        session()->flash('status', 'Selected todos restored successfully.');

        return response()->json(['success' => true]);
    }

    public function toggleComplete($id)
    {
        $this->mutateWithActivity(function () use ($id): Todo {
            $todo = auth()->user()->todos()->findOrFail($id);
            $isCompleting = $todo->status !== TodoStatus::Done;
            $todo->status = $isCompleting ? TodoStatus::Done : TodoStatus::Pending;
            $todo->save();

            return $todo;
        }, 'status_updated', 'Updated completion status');

        return response()->json(['success' => true]);
    }

    public function updatePriority(Request $request, $id)
    {
        $validated = $request->validate([
            'priority' => ['required', 'integer', Rule::in(array_map(fn (TodoPriority $priority) => $priority->value, TodoPriority::cases()))],
        ]);

        $this->mutateWithActivity(function () use ($validated, $id): Todo {
            $todo = auth()->user()->todos()->findOrFail($id);
            $todo->update(['priority' => $validated['priority']]);

            return $todo;
        }, 'priority_updated', 'Updated todo priority');

        return response()->json(['success' => true]);
    }

    public function updateDueDate(Request $request, $id)
    {
        $validated = $request->validate([
            'due_date' => ['nullable', 'date'],
        ]);

        $this->mutateWithActivity(function () use ($validated, $id): Todo {
            $todo = auth()->user()->todos()->findOrFail($id);
            $todo->update(['due_date' => $validated['due_date']]);

            return $todo;
        }, 'due_date_updated', 'Updated todo due date');

        return response()->json(['success' => true]);
    }

    /**
     * Execute a todo mutation and its activity log write in one database transaction.
     *
     * @param callable(): Todo $mutation
     */
    private function mutateWithActivity(callable $mutation, string $action, string $description): Todo
    {
        return DB::transaction(function () use ($mutation, $action, $description): Todo {
            $todo = $mutation();
            ActivityLog::add($action, $todo, $description);

            return $todo;
        });
    }
}
