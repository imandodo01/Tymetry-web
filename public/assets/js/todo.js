var table;
var searchDebounceTimer;

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
        table.draw();
    }, 300);
}

$('#searchTitle').on('input search', scheduleTodoSearch);

$('#filterStatus, #filterPriority').on('change', function () {
    table.draw();
});

$('#resetTodoFilters').on('click', function () {
    clearTimeout(searchDebounceTimer);
    $('#searchTitle').val('');
    $('#filterStatus').val('');
    $('#filterPriority').val('');
    table.draw();
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
