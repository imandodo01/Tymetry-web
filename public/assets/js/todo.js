
var table;

function toggleBulkButtons() {
    let hasSelected = $('.todo-checkbox:checked').length > 0;

    $('#bulkArchive').prop('disabled', !hasSelected);
    $('#bulkDone').prop('disabled', !hasSelected);
}

$(function () {
    toggleBulkButtons();
    table = $('#todoTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ajax: {
            url: "todos",
            data: function (d) {
                d.search_title = $('#searchTitle').val();
                d.status = $('#filterStatus').val();
                d.priority = $('#filterPriority').val();
            },
            fail: function (xhr) {
                ajaxFailHandler(xhr);
            }
        },
        columns: [
            { data: 'checkbox' },
            { data: 'title' },
            { data: 'status_badge' },
            { data: 'status_action' },
            { data: 'priority_badge' },
            { data: 'due_status' },
            { data: 'action' },
        ],
        columnDefs: [
            {
                targets: [0, 6],
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

    table.on('draw', function () {
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
        fail: function (xhr) {
            ajaxFailHandler(xhr);
        }
    });
});

$('#searchTitle').keyup(function () {
    table.draw();
});

$('#filterStatus, #filterPriority').change(function () {
    table.draw();
});

$('#createTodoBtn').click(function () {
    $('#todoModalBody').load('todos/create', function () {
        $('#todoModal').modal('show');
    });
});

$(document).on('click', '.editTodoBtn', function () {
    let id = $(this).data('id');
    console.log($(this).data());
    $('#todoModalBody').load('todos/' + id + '/edit'.replace(':id', id), function () {
        $('#todoModal').modal('show');
    });
});

function getSelectedTodos() {
    let ids = [];
    $('.todo-checkbox:checked').each(function () {
        ids.push($(this).val());
    });
    return ids;
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
            showToast('Todos archived');
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
    $('#todoModalBody').html(`
        <div class="text-center py-5">
            <div class="spinner-border spinner-border-sm">
            </div>
        </div>`);
    confirmAction('Mark as Done selected todos?', function () {
        buttonLoading(btn, true, 'Archiving...');
        $.post('todos/bulk/done', {
            _token: $('meta[name="csrf-token"]').attr('content'),
            ids: ids
        }, function () {
            table.ajax.reload(null, false);
            showToast('Status updated');
        }).done(function () {
            showToast('Todos archived');
        }).always(function () {
            buttonLoading(btn, false);
        }).fail(function (xhr) {
            ajaxFailHandler(xhr);
        });
    });
});

$('#checkAll').change(function () {
    $('.todo-checkbox').prop('checked', $(this).prop('checked'));
    toggleBulkButtons();
});

$(document).on('change', '.todo-checkbox', function () {
    $('#checkAll').prop('checked', $('.todo-checkbox:checked').length === $('.todo-checkbox').length);
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
        fail: function (xhr) {
            ajaxFailHandler(xhr);
        }
    });
});
$(document).on('change', '.inlinePriority', function () {
    let select = $(this);
    $.ajax({
        url: `todo/${select.data('id')}/priority`,
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            priority: select.val()
        },
        success: function () {
            showToast('Priority updated');
        }
    });
});

$(document).on('change', '.inlineDueDate', function () {
    let input = $(this);
    $.ajax({
        url: `todo/${input.data('id')}/due-date`,
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            due_date: input.val()
        },
        success: function () {
            showToast('Due date updated');
        }
    });
});

$(document).keydown(function (e) {
    if ($(e.target).is('input, textarea')) {
        return;
    }

    if (e.key === 'n') {
        $('#createTodoBtn').click();
    }

    if (e.key === '/') {
        e.preventDefault();
        $('#todoSearch').focus();
    }
});
