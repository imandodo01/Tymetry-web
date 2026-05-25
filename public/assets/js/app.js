window.showToast = function (message = 'Success') {
    const toastEl = document.getElementById('appToast');
    toastEl.querySelector('.toast-body').innerText = message;

    const toast = new bootstrap.Toast(toastEl);
    toast.show();
}

$(document).ajaxError(function (event, xhr) {
    if (xhr.status === 422) {
        showToast('Validation failed');
        return;
    }

    if (xhr.status === 403) {
        showToast('Unauthorized action');
        return;
    }

    if (xhr.status === 500) {
        showToast('Server error');
        return;
    }

    showToast('Something went wrong');
});

window.confirmAction = function (message, callback) {
    $('#confirmMessage').text(message);
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();
    $('#confirmButton').off('click').on('click', function () {
        callback();
        modal.hide();
    });
};

window.buttonLoading = function (button, loading = true, text = 'Processing...') {
    if (loading) {
        button.data('original-text', button.html());

        button.prop('disabled', true);
        button.html(`<span class="spinner-border spinner-border-sm me-2"></span>${text}`);
        return;
    }
    button.prop('disabled', false);
    button.html(button.data('original-text'));
}
