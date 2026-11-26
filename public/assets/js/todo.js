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
    $('#exportSelectedTodos').prop('disabled', !hasSelected);
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
        const card = $('<article class="todo-board-card"></article>');
        card.append('<a class="todo-board-card-title" href="' + escapeTodoHtml(todo.edit_url) + '">' +
            escapeTodoHtml(todo.title) + '</a>');
        card.append('<div class="todo-board-card-meta"><span class="badge ' +
            escapeTodoHtml(todo.priority_badge) + '">' + escapeTodoHtml(todo.priority_label) +
            '</span></div>');
        card.append(dueText);
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


function formatTymxValue(value) {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    return String(value);
}

function renderTymxImportPreview(response) {
    const summary = response.summary || {};
    const summaryLabels = [
        ['Total', summary.total || 0],
        ['Proposed updates', summary.updates || 0],
        ['Unchanged', summary.unchanged || 0],
        ['Conflicts', summary.conflicts || 0],
        ['Unmatched', summary.unmatched || 0]
    ];

    const summaryContainer = $('#tymxImportSummary').empty();
    summaryLabels.forEach(function ([label, count]) {
        $('<span class="badge text-bg-light border"></span>')
            .text(label + ': ' + count)
            .appendTo(summaryContainer);
    });

    const rows = $('#tymxImportRows').empty();
    (response.records || []).forEach(function (record) {
        const row = $('<tr></tr>');
        $('<td></td>')
            .append($('<div class="fw-semibold"></div>').text(record.title || 'Unmatched Todo'))
            .append($('<small class="text-muted"></small>').text(record.public_id))
            .appendTo(row);

        const actionLabels = {
            update: 'Proposed update',
            unchanged: 'No proposed change',
            conflict: 'Conflict',
            unmatched: 'Unmatched — skipped'
        };
        $('<td></td>')
            .append($('<span class="badge text-bg-light border"></span>').text(actionLabels[record.action] || record.action))
            .appendTo(row);

        const comparison = $('<td></td>');
        (record.changes || []).forEach(function (change) {
            const item = $('<div class="small mb-1"></div>');
            item.append($('<span class="fw-semibold"></span>').text(change.field + ': '));
            const fromValue = change.resolution === 'server_only'
                ? change.base
                : change.current;
            const toValue = change.resolution === 'server_only'
                ? change.current
                : change.proposed;
            item.append(document.createTextNode(
                formatTymxValue(fromValue) + ' → ' + formatTymxValue(toValue)
            ));
            const resolutionLabels = {
                conflict: 'Conflict',
                proposed: 'Eligible to update',
                server_only: 'Server changed — preserve',
                already_current: 'Already current'
            };
            const resolutionClasses = {
                conflict: 'text-bg-warning',
                proposed: 'text-bg-success',
                server_only: 'text-bg-light border',
                already_current: 'text-bg-light border'
            };
            if (resolutionLabels[change.resolution]) {
                item.append($('<span class="badge ms-2"></span>')
                    .addClass(resolutionClasses[change.resolution])
                    .text(resolutionLabels[change.resolution]));
            }
            comparison.append(item);
        });
        if (record.action === 'unmatched') {
            comparison.text('No active Todo in your account matches this public ID.');
        } else if (!record.changes || record.changes.length === 0) {
            comparison.text('No proposed field changes.');
        }
        comparison.appendTo(row);
        rows.append(row);
    });

    if ((response.records || []).length === 0) {
        rows.append($('<tr><td colspan="3" class="text-center text-muted py-4">No Todos in this file.</td></tr>'));
    }

    $('#tymxImportPreview').removeClass('d-none');
}

$('#tymxImportForm').on('submit', function (event) {
    event.preventDefault();

    const form = this;
    const fileInput = $('#tymxImportFile')[0];
    const errorContainer = $('#tymxImportError');
    const previewContainer = $('#tymxImportPreview');
    const button = $('#tymxPreviewButton');

    errorContainer.addClass('d-none').text('');
    previewContainer.addClass('d-none');

    if (!fileInput.files || fileInput.files.length === 0) {
        errorContainer.text('Choose a .tymx file first.').removeClass('d-none');
        return;
    }

    const formData = new FormData(form);
    button.prop('disabled', true).text('Validating...');

    $.ajax({
        url: $(form).data('preview-url'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    }).done(function (response) {
        renderTymxImportPreview(response);
    }).fail(function (xhr) {
        const message = xhr.responseJSON?.errors?.file?.[0]
            || xhr.responseJSON?.message
            || 'The TYMX file could not be validated.';
        errorContainer.text(message).removeClass('d-none');
    }).always(function () {
        button.prop('disabled', false).text('Validate & preview');
    });
});
