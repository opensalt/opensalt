const wizardEl = document.getElementById('wizard');
const bootstrapApi = window.bootstrap;
if (wizardEl && bootstrapApi?.Modal) {
    const redirectUrl = wizardEl.dataset.redirectUrl;
    const modal = bootstrapApi.Modal.getOrCreateInstance(wizardEl);

    if (redirectUrl) {
        wizardEl.addEventListener('hide.bs.modal', () => {
            window.location.href = redirectUrl;
        });
    }

    modal.show();
}
