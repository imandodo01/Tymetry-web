<div class="mb-4">
    <label for="title" class="form-label">Title</label>
    <input type="text" id="title" name="title" class="form-control @error('title') is-invalid @enderror"
        value="{{ old('title', $todo->title ?? '') }}">

    @error('title')
    <span class="invalid-feedback">
        {{ $message }}
    </span>
    @enderror
</div>

<div class="mb-4">
    <label for="description" class="form-label">Description</label>
    <textarea id="description" name="description" rows="4"
        class="form-control">{{ old('description', $todo->description ?? '') }}</textarea>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror">
            @foreach ($statusOption as $status)
            <option value="{{ $status->value }}" @selected(old('status', $todo->status->value ?? '') == $status->value)>
                {{ $status->label() }}
            </option>
            @endforeach
        </select>
        @error('status')
        <span class="invalid-feedback">
            {{ $message }}
        </span>
        @enderror
    </div>
    <div class="col-md-6 mb-4">
        <label for="priority" class="form-label">Priority</label>
        <select id="priority" name="priority" class="form-select @error('priority') is-invalid @enderror">
            @foreach ($priorityOption as $priority)
            <option value="{{ $priority->value }}" @selected(old('priority', $todo->priority->value ?? '') == $priority->value)>
                {{ $priority->label() }}
            </option>
            @endforeach
        </select>
        @error('priority')
        <span class="invalid-feedback">
            {{ $message }}
        </span>
        @enderror
    </div>
</div>

<div class="mb-4">
    <label for="due_date" class="form-label">Due Date</label>
    <input type="date" id="due_date" name="due_date" class="form-control @error('due_date') is-invalid @enderror"
        value="{{ old('due_date', !empty($todo->due_date) ? \Carbon\Carbon::parse($todo->due_date)->format('Y-m-d') : '') }}">
    @error('due_date')
    <span class="invalid-feedback">
        {{ $message }}
    </span>
    @enderror
</div>
<div class="mb-4">
    <label for="due_time" class="form-label">Due Time</label>
    <input type="time" id="due_time" name="due_time" class="form-control @error('due_time') is-invalid @enderror"
        value="{{ old('due_time', $todo->due_time ?? '') }}">
    @error('due_time')
    <span class="invalid-feedback">
        {{ $message }}
    </span>
    @enderror
</div>
