function submitTymxExport(selection, ids) {
    const form = $('<form>', { method: 'POST', action: $('#exportSelectedTodos').data('export-url'), class: 'd-none' });
    form.append($('<input>', { type: 'hidden', name: '_token', value: $('meta[name="csrf-token"]').attr('content') }));
    form.append($('<input>', { type: 'hidden', name: 'selection', value: selection }));
    (ids || []).forEach(id => form.append($('<input>', { type: 'hidden', name: 'ids[]', value: id })));
    $('body').append(form); form[0].submit(); form.remove();
}
$(document).on('click', '#exportSelectedTodos', function () {
    const ids = $('.todo-checkbox:checked').map(function () { return $(this).val(); }).get();
    if (!ids.length) { showToast('Select at least one task to export'); return; }
    submitTymxExport('selected', ids);
});
$(document).on('click', '#exportAllTodos', function () {
    confirmAction('Export all active tasks to a .tymx file?', function () { submitTymxExport('all_active', []); });
});
