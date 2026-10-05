import DataTable from 'datatables.net-bs5';

function formatLogTimestamp(data) {
    const pad = (num) => (num >= 0 && num < 10 ? `0${num}` : String(num));
    const ts = new Date(data.replace(' ', 'T').replace(/\..*$/, 'Z'));
    const date = [ts.getFullYear(), pad(ts.getMonth() + 1), pad(ts.getDate())].join('-');
    const time = [pad(ts.getHours()), pad(ts.getMinutes()), pad(ts.getSeconds())].join(':');
    return `${date} ${time}`;
}

function initSystemLogTable() {
    const tableEl = document.getElementById('systemLogTable');
    if (!tableEl) {
        return;
    }

    const ajaxUrl = tableEl.dataset.logsUrl;
    if (!ajaxUrl) {
        return;
    }

    if (DataTable.isDataTable(tableEl)) {
        DataTable.getInstance(tableEl).ajax.reload();
        return;
    }

    new DataTable(tableEl, {
        ajax: ajaxUrl,
        dataSrc: 'data',
        order: [[0, 'desc']],
        columns: [
            {
                data: 'changed_at',
                render(data, type) {
                    if (type !== 'display' && type !== 'filter') {
                        return data;
                    }
                    return formatLogTimestamp(data);
                },
            },
            { data: 'description' },
            { data: 'username' },
        ],
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSystemLogTable);
} else {
    initSystemLogTable();
}
