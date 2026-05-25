<div class="mb-4">
    <label class="form-label">Title</label>
    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
        value="{{ old('title', $todo->title ?? '') }}">

    @error('title')
    <span class="invalid-feedback">
        {{ $message }}
    </span>
    @enderror

</div>

<div class="mb-4">
    <label class="form-label">Description</label>
    <textarea name="description" rows="4" class="form-control">{{ old('title', $todo->description ?? '') }}</textarea>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            @foreach ($statusOption as $status)
            <option value="{{ $status->value }}" @selected( old('status', $todo->status->value ?? '') ==
                $status->value)>
                {{ $status->label() }}
            </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-6 mb-4">
        <label class="form-label">Priority</label>
        <select name="priority" class="form-select">
            @foreach ($priorityOption as $priority)
            <option value="{{ $priority->value }}" @selected( old('priority', $todo->priority->value ?? '')
                == $priority->value)>
                {{ $priority->label() }}
            </option>
            @endforeach
        </select>
    </div>
</div>

<div class="mb-4">
    <label class="form-label">Due Date</label>
    <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror"
        value="{{ old('due_date', $todo->due_date ?? '' ? \Carbon\Carbon::parse($todo->due_date)->format('Y-m-d') : '') }}"">
                @error('due_date')
                <span class=" invalid-feedback">
    {{ $message }}
    </span>
    @enderror
</div>