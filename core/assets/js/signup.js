function initSignupForm() {
    const orgSelect = document.getElementById('signup_org');
    const newOrgInput = document.getElementById('signup_newOrg');
    const newOrgBlock = document.querySelector('.signup_new_org');
    const addOrgMessage = document.querySelector('.js-add-org-message');
    const submitBtn = document.querySelector('input[type="submit"]');

    if (!orgSelect || !newOrgInput || !newOrgBlock) {
        return;
    }

    const orgNames = [];
    orgSelect.querySelectorAll('option').forEach((option) => {
        if (option.value) {
            orgNames.push(option.text);
        }
    });

    const errorHtml =
        '<span class="help-block">' +
        '<ul class="list-unstyled"><li><i class="fa fa-exclamation-circle"></i> Choose this group from the Group list' +
        '</li></ul></span>';

    function removeError() {
        newOrgBlock.classList.remove('has-error');
        const help = newOrgInput.parentElement?.querySelector('.help-block');
        help?.remove();
        submitBtn?.classList.remove('disabled');
    }

    function showHideOtherOrg() {
        const isOther = orgSelect.value === 'other';
        newOrgBlock.classList.toggle('d-none', !isOther);
        newOrgBlock.style.display = isOther ? '' : 'none';
        if (addOrgMessage) {
            addOrgMessage.classList.toggle('d-none', isOther);
        }
        newOrgInput.required = isOther;
    }

    if (newOrgInput.value !== '') {
        orgSelect.value = 'other';
        showHideOtherOrg();
    }

    orgSelect.addEventListener('change', () => {
        removeError();
        newOrgInput.value = '';
        showHideOtherOrg();
    });

    newOrgInput.addEventListener('blur', () => {
        removeError();
        if (orgNames.includes(newOrgInput.value)) {
            newOrgBlock.classList.add('has-error');
            newOrgInput.insertAdjacentHTML('afterend', errorHtml);
            submitBtn?.classList.add('disabled');
        }
    });

    const bootstrapApi = window.bootstrap;
    if (!bootstrapApi?.Popover) {
        return;
    }

    document.querySelectorAll('[data-bs-toggle="popover"]').forEach((el) => {
        bootstrapApi.Popover.getOrCreateInstance(el, {
            trigger: 'hover',
            html: true,
            placement: () => (window.innerWidth < 1100 ? 'bottom' : 'left'),
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSignupForm);
} else {
    initSignupForm();
}
