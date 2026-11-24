(() => {
    const forms = document.querySelectorAll('[data-auth-form]');

    forms.forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');

            if (!button) {
                return;
            }

            button.disabled = true;
            button.dataset.originalText = button.textContent;
            button.textContent = 'Please wait...';
        });
    });
})();
