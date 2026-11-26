var table;
var searchDebounceTimer;
var boardRequest;
var isBoardView = false;

function toggleBulkButtons() {
    const selectedCount = $('.todo-checkbox:checked').length;
    const totalCount = $('.todo-checkbox').length;
    const hasSelected = selectedCount > 0;
    const checkAll = $('#checkAll');

    $('#bulkArchive, #bulkDone').prop('disabled', !hasSelected);
    $('#selectedTodoCount').text(selectedCount + ' selected');

    checkAll.prop('checked', totalCount > 0 && selectedCount === totalCount);
    checkAll.prop('indeterminate', selectedCount > 0 && selectedCount < totalCount);
}

$(function () {
    toggleBulkButtons();
    // Keep DataTables failures in the page-level error state instead of showing its default alert.
    $.fn.dataTable.ext.errMode = 'none';

    table = $('#todoTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ajax: {
            url: "todos",
            beforeSend: function () {
                $('#todoTableLoading').removeClass('d-none');
                $('#todoTableError').addClass('d-none');
            },
            complete: function () {
                $('#todoTableLoading').addClass('d-none');
            },
            data: function (d) {
                d.search_title = $('#searchTitle').val();
                d.status = $('#filterStatus').val();
                d.priority = $('#filterPriority').val();
            },
            error: function (xhr) {
                ajaxFailHandler(xhr, { notify: false });
                $('#todoTableLoading').addClass('d-none');
                $('#todoTableError').removeClass('d-none');
            }
        },
        columns: [
            { data: 'checkbox' },
            { data: 'title' },
            { data: 'status_badge' },
            { data: 'priority_action' },
            { data: 'due_status' },
            { data: 'status_action' },
            { data: 'due_action' },
            { data: 'action' },
        ],
        columnDefs: [
            {
                targets: [0, 7],
                orderable: false,
                searchable: false
            }
        ],
        language: {
            emptyTable: `
                    <div class="py-5 text-center">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <div class="mt-3 fw-semibold"> No todos found </div>
                        <small class="text-muted">
                            Start by creating your first task
                        </small>
                    </div>`,
            zeroRecords: `
                    <div class="py-5 text-center">
                        <i class="bi bi-search fs-1 text-muted"></i>
                        <div class="mt-3 fw-semibold"> No matching result </div>
                        <small class="text-muted">
                            Try adjusting your filters
                        </small>
                    </div>`
        }
    });

    $('#showTodoList').on('click', function () {
        setTodoView('list');
    });

    $('#showTodoBoard').on('click', function () {
        setTodoView('board');
    });

    $('#retryTodoBoard').on('click', loadTodoBoard);

    $('#retryTodoTable').on('click', function () {
        table.ajax.reload(null, false);
    });

    table.on('preXhr', function () {
        // Clear the previous page's selection as soon as a new page/filter request starts.
        $('.todo-checkbox').prop('checked', false);
        $('#checkAll').prop({ checked: false, indeterminate: false });
        toggleBulkButtons();
    });

    table.on('draw', function () {
        // Selection is intentionally limited to the currently rendered page.
        $('#checkAll').prop({ checked: false, indeterminate: false });
        toggleBulkButtons();
    });
});

$(document).on('change', '.todo-status', function () {
    let id = $(this).data('id');
    $.ajax({
        url: 'todos/' + id + '/status',
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            status: $(this).val()
        },
        success: function () {
            $('#todoTable').DataTable().ajax.reload(null, false);
            showToast('Status updated');
        },
        error: function (xhr) {
            ajaxFailHandler(xhr);
        }
    });
});

function scheduleTodoSearch() {
    clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(function () {
        if (isBoardView) {
            loadTodoBoard();
        } else {
            table.draw();
        }
    }, 300);
}

$('#searchTitle').on('input search', scheduleTodoSearch);

$('#filterStatus, #filterPriority').on('change', function () {
    if (isBoardView) {
        loadTodoBoard();
    } else {
        table.draw();
    }
});

$('#resetTodoFilters').on('click', function () {
    clearTimeout(searchDebounceTimer);
    $('#searchTitle').val('');
    $('#filterStatus').val('');
    $('#filterPriority').val('');
    if (isBoardView) {
        loadTodoBoard();
    } else {
        table.draw();
    }
});

function getSelectedTodos() {
    return $('.todo-checkbox:checked').map(function () {
        return $(this).val();
    }).get();
}

$('#bulkArchive').click(function () {
    let btn = $(this);
    let ids = getSelectedTodos();
    if (ids.length === 0) {
        showToast('Please select at least one todo');
        return;
    }
    confirmAction('Archive selected todos?', function () {
        buttonLoading(btn, true, 'Archiving...');
        $.post('todos/bulk/archive', {
            _token: $('meta[name="csrf-token"]').attr('content'),
            ids: ids
        }, function () {
            table.ajax.reload(null, false);
        }).done(function () {
            showToast('Todos archived');
        }).always(function () {
            buttonLoading(btn, false);
        }).fail(function (xhr) {
            ajaxFailHandler(xhr);
        });
    });
});

$('#bulkDone').click(function () {
    let btn = $(this);
    let ids = getSelectedTodos();
    if (ids.length === 0) {
        showToast('Please select at least one todo');
        return;
    }
    confirmAction('Mark as Done selected todos?', function () {
        buttonLoading(btn, true, 'Marking done...');
        $.post('todos/bulk/done', {
            _token: $('meta[name="csrf-token"]').attr('content'),
            ids: ids
        }, function () {
            table.ajax.reload(null, false);
        }).done(function () {
            showToast('Todos marked done');
        }).always(function () {
            buttonLoading(btn, false);
        }).fail(function (xhr) {
            ajaxFailHandler(xhr);
        });
    });
});

$('#checkAll').on('change', function () {
    $('.todo-checkbox').prop('checked', $(this).prop('checked'));
    toggleBulkButtons();
});

$(document).on('change', '.todo-checkbox', toggleBulkButtons);

$(document).on('click', '.toggleCompleteBtn', function () {
    let id = $(this).data('id');
    $.ajax({
        url: `todos/${id}/complete`,
        type: 'POST',
        data: {
            _token:
                $('meta[name="csrf-token"]')
                    .attr('content')
        },
        success: function () {
            table.ajax.reload(null, false);
            showToast('Todo updated');
        },
        error: function (xhr) {
            ajaxFailHandler(xhr);
        }
    });
});
$(document).on('change', '.inlinePriority', function () {
    let select = $(this);
    $.ajax({
        url: `todos/${select.data('id')}/priority`,
        type: 'PATCH',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            priority: select.val()
        },
        success: function () {
            table.ajax.reload(null, false);
            showToast('Priority updated');
        },
        error: function (xhr) {
            ajaxFailHandler(xhr);
        }
    });
});

$(document).on('change', '.inlineDueDate', function () {
    let input = $(this);
    $.ajax({
        url: `todos/${input.data('id')}/due-date`,
        type: 'PATCH',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            due_date: input.val()
        },
        success: function () {
            table.ajax.reload(null, false);
            showToast('Due date updated');
        },
        error: function (xhr) {
            ajaxFailHandler(xhr);
        }
    });
});

function setTodoView(view) {
    isBoardView = view === 'board';

    $('#todoListView').toggleClass('d-none', isBoardView);
    $('#todoBoardView').toggleClass('d-none', !isBoardView);
    $('#showTodoList')
        .toggleClass('btn-dark', !isBoardView)
        .toggleClass('btn-outline-dark', isBoardView)
        .attr('aria-pressed', String(!isBoardView));
    $('#showTodoBoard')
        .toggleClass('btn-dark', isBoardView)
        .toggleClass('btn-outline-dark', !isBoardView)
        .attr('aria-pressed', String(isBoardView));

    if (isBoardView) {
        loadTodoBoard();
    }
}

function escapeTodoHtml(value) {
    return $('<div>').text(value ?? '').html();
}

function renderTodoBoard(todos) {
    const columns = [0, 1, 2];
    columns.forEach(function (status) {
        $('[data-column-cards="' + status + '"]').empty();
        $('[data-column-count="' + status + '"]').text('0');
    });

    todos.forEach(function (todo) {
        const dueText = todo.due_date
            ? '<div class="todo-board-due"><span class="badge ' + escapeTodoHtml(todo.due_badge) + '">' +
                escapeTodoHtml(todo.due_status === 'overdue' ? 'Overdue' : todo.due_label) +
                '</span></div>'
            : '<div class="todo-board-due text-muted">No due date</div>';
        const card = $('<article class="todo-board-card" draggable="true"></article>');
        card.attr('data-todo-id', todo.id);
        card.attr('data-current-status', todo.status);
        card.append('<div class="todo-board-card-top"><span class="todo-board-drag-handle" aria-hidden="true"><i class="bi bi-grip-vertical"></i> Drag</span><span class="todo-board-card-saving d-none" role="status">Saving...</span></div>');
        card.append('<a class="todo-board-card-title" href="' + escapeTodoHtml(todo.edit_url) + '">' +
            escapeTodoHtml(todo.title) + '</a>');
        card.append('<div class="todo-board-card-meta"><span class="badge ' +
            escapeTodoHtml(todo.priority_badge) + '">' + escapeTodoHtml(todo.priority_label) +
            '</span></div>');
        card.append(dueText);

        const statusControl = $('<label class="todo-board-status-control"><span>Move to</span><select class="form-select form-select-sm todo-board-status" aria-label="Change status for task"></select></label>');
        [
            { value: 0, label: 'Pending' },
            { value: 1, label: 'In Progress' },
            { value: 2, label: 'Done' }
        ].forEach(function (status) {
            statusControl.find('select').append(
                $('<option></option>').val(status.value).text(status.label)
            );
        });
        statusControl.find('select')
            .val(String(todo.status))
            .attr('data-id', todo.id)
            .attr('data-original-status', todo.status);
        statusControl.find('select').attr('aria-label', 'Change status for ' + todo.title);
        card.append(statusControl);

        $('[data-column-cards="' + todo.status + '"]').append(card);
    });

    columns.forEach(function (status) {
        const count = $('[data-column-cards="' + status + '"] .todo-board-card').length;
        $('[data-column-count="' + status + '"]').text(count);
    });

    $('#todoBoardEmpty').toggleClass('d-none', todos.length > 0);
    $('#todoBoardColumns').toggleClass('d-none', todos.length === 0);
}

function loadTodoBoard() {
    if (boardRequest) {
        boardRequest.abort();
    }

    $('#todoBoardLoading').removeClass('d-none');
    $('#todoBoardError').addClass('d-none');

    const request = $.ajax({
        url: 'todos/board-data',
        type: 'GET',
        data: {
            search_title: $('#searchTitle').val(),
            status: $('#filterStatus').val(),
            priority: $('#filterPriority').val()
        }
    });

    boardRequest = request;

    request.done(function (response) {
        renderTodoBoard(response.data || []);
    }).fail(function (xhr, status) {
        if (status !== 'abort') {
            ajaxFailHandler(xhr, { notify: false });
            $('#todoBoardError').removeClass('d-none');
        }
    }).always(function () {
        if (boardRequest === request) {
            $('#todoBoardLoading').addClass('d-none');
            boardRequest = null;
        }
    });
}

function updateBoardTodoStatus(todoId, status, card) {
    if (card.attr('data-saving') === 'true') {
        return;
    }

    const originalStatus = Number(card.attr('data-current-status'));
    const requestedStatus = Number(status);

    if (requestedStatus === originalStatus) {
        card.find('.todo-board-status').val(String(originalStatus));
        return;
    }

    card.attr('data-saving', 'true')
        .addClass('is-saving')
        .find('.todo-board-card-saving').removeClass('d-none');
    card.find('.todo-board-status').prop('disabled', true);
    card.attr('draggable', 'false');

    $.ajax({
        url: 'todos/' + todoId + '/status',
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            status: requestedStatus
        }
    }).done(function () {
        showToast('Task status updated');
        loadTodoBoard();
    }).fail(function (xhr) {
        ajaxFailHandler(xhr, { notify: false });
        showToast('Unable to update task status. The previous status has been restored.');
        loadTodoBoard();
    }).always(function () {
        card.removeClass('is-saving');
    });
}

$(document).on('change', '.todo-board-status', function () {
    const select = $(this);
    const card = select.closest('.todo-board-card');
    updateBoardTodoStatus(select.data('id'), select.val(), card);
});

$(document).on('dragstart', '.todo-board-card', function (event) {
    const originalEvent = event.originalEvent;
    if ($(event.target).closest('a, select, button').length || $(this).attr('data-saving') === 'true') {
        event.preventDefault();
        return;
    }

    originalEvent.dataTransfer.effectAllowed = 'move';
    originalEvent.dataTransfer.setData('text/plain', String($(this).data('todo-id')));
    $(this).addClass('is-dragging');
});

$(document).on('dragend', '.todo-board-card', function () {
    $(this).removeClass('is-dragging');
    $('.todo-board-column').removeClass('is-drop-target');
});

$(document).on('dragover', '.todo-board-column', function (event) {
    event.preventDefault();
    event.originalEvent.dataTransfer.dropEffect = 'move';
    $(this).addClass('is-drop-target');
});

$(document).on('dragleave', '.todo-board-column', function (event) {
    if (!this.contains(event.relatedTarget)) {
        $(this).removeClass('is-drop-target');
    }
});

$(document).on('drop', '.todo-board-column', function (event) {
    event.preventDefault();
    $('.todo-board-column').removeClass('is-drop-target');

    const todoId = event.originalEvent.dataTransfer.getData('text/plain');
    const card = $('.todo-board-card[data-todo-id="' + todoId + '"]');
    if (!card.length || card.attr('data-saving') === 'true') {
        return;
    }

    updateBoardTodoStatus(todoId, $(this).data('status'), card);
});
