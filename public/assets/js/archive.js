var table;

function toggleBulkButtons() {
    let hasSelected = $('.todo-checkbox:checked').length > 0;

    $('#bulkRestore').prop('disabled', !hasSelected);
}
$(function () {
    toggleBulkButtons();
});

function getSelectedTodos() {
    let ids = [];
    $('.todo-checkbox:checked').each(function () {
        ids.push($(this).val());
    });
    return ids;
}

$('#bulkRestore').click(function () {

    let btn = $(this);
    let ids = getSelectedTodos();
    if (ids.length === 0) {
        showToast('Please select at least one todo');
        return;
    }
    confirmAction('Restore selected todos?', function () {
        buttonLoading(btn, true, 'Restoring...');
        $.post('bulk/restore', {
            _token: $('meta[name="csrf-token"]').attr('content'),
            ids: ids
        }, function () {
            showToast('Todos restored');
            location.reload();
        }).done(function () {
            showToast('Todos restored');
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
