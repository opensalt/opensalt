/**
 * Framework import wizard: imports CASE files and spreadsheets from the
 * #wizard modal included on /cfdoc/ and /cfdoc/import. The wizard template
 * calls SaltLocal.handleFile()/SaltLocal.handleExcelFile() from inline
 * onclick handlers, so SaltLocal must stay a global.
 */
import { spinner } from '../util-salt';

function reloadPage() {
    if (window.location.pathname === '/cfdoc/import') {
        window.location.href = '/cfdoc/';
    } else {
        window.location.reload();
    }
}

/** Base64-encodes file content in the form the import endpoints expect. */
function encodeContent(content) {
    return window.btoa(
        encodeURIComponent(content).replace(/%([0-9A-F]{2})/g, (match, p1) => String.fromCharCode('0x' + p1)),
    );
}

function showLoading(message) {
    document.querySelector('.tab-content')?.classList.add('d-none');
    const loadingBody = document.querySelector('.file-loading .col-md-12');
    if (loadingBody) {
        loadingBody.innerHTML = spinner(message);
    }
    document.querySelector('.file-loading')?.classList.remove('d-none');
    document.querySelector('.case-error-msg')?.classList.add('d-none');
}

function showError(message) {
    document.querySelector('.tab-content')?.classList.remove('d-none');
    document.querySelector('.file-loading')?.classList.add('d-none');
    const error = document.querySelector('.case-error-msg');
    if (error) {
        error.textContent = message;
        error.classList.remove('d-none');
    }
}

/**
 * Pulls the server-provided error message out of a failed import response,
 * falling back to the caller's generic message when the body is not JSON.
 */
async function responseErrorMessage(response, fallback) {
    try {
        const data = await response.json();
        if (typeof data?.error === 'string' && data.error.length > 0) {
            return data.error;
        }
    } catch (error) {
        // response body was not JSON — use the fallback message
    }
    return fallback;
}

const Import = {
    async case(fileContent) {
        showLoading('Loading file');
        try {
            const response = await fetch('/salt/case/import', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                body: new URLSearchParams({fileContent: encodeContent(fileContent)}),
            });
            if (!response.ok) {
                showError(await responseErrorMessage(response, 'Error while importing the file'));
                return;
            }
            reloadPage();
        } catch (error) {
            showError('Error while importing the file');
        }
    },
};

function isTypeValid(filename) {
    return ['xls', 'xlsx', 'json', 'csv'].includes(filename.split('.').pop().toLowerCase());
}

function handleFileSelect(fileType, inputId) {
    const file = document.getElementById(inputId)?.files?.[0];
    if (!file) {
        return;
    }

    if (!isTypeValid(file.name)) {
        showError('File type not allowed');
        return;
    }

    const reader = new FileReader();
    reader.onload = (event) => {
        if ('case' === fileType) {
            Import.case(event.target.result);
        } else {
            showError('File type not allowed');
        }
    };
    reader.readAsText(file);
}

function handleExcelFile() {
    const file = document.getElementById('excel-url')?.files?.[0];
    if (!file) {
        return;
    }

    if (!isTypeValid(file.name)) {
        showError('File type not allowed');
        return;
    }

    const fallbackMessage = "We're sorry, we cannot load this document. Please ensure this document is not already on the server, or see the Spreadsheet loading guide at docs.opensalt.org";
    showLoading('Loading file');
    const data = new FormData();
    data.append('file', file);

    (async () => {
        try {
            const response = await fetch('/salt/excel/import', {method: 'POST', body: data});
            if (!response.ok) {
                showError(await responseErrorMessage(response, fallbackMessage));
                return;
            }
            reloadPage();
        } catch (error) {
            showError(fallbackMessage);
        }
    })();
}

const SaltLocal = {
    handleFile: handleFileSelect,
    handleExcelFile: handleExcelFile,
};
globalThis.SaltLocal = SaltLocal;
