
var table;
$(function () {
    table = $('#todoTable').DataTable({
        processing: true,
        serverSide: true,
        searching: false,
        ajax: {
            url: "{{ route('todo.index') }}",
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
            { data: 'title' },
            { data: 'status_action' },
            { data: 'priority_badge' },
            { data: 'action' },
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
});

$(document).on('change', '.todo-status', function () {
    let id = $(this).data('id');
    $.ajax({
        url: '{{ route("todo.status", ":id") }}'.replace(':id', id),
        type: 'POST',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            status: $(this).val()
        },
        success: function () {
            $('#todoTable').DataTable().ajax.reload(null, false);
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
