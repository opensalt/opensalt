function initUserListGroupFilter() {
    const table = document.getElementById('datatable');
    const orgFilter = document.getElementById('search_form_organization');
    if (!table || !orgFilter) {
        return;
    }

    const rows = table.querySelectorAll('tbody tr');

    orgFilter.addEventListener('input', () => {
        const query = orgFilter.value.trim().toLowerCase();
        rows.forEach((row) => {
            const groupCell = row.cells[1];
            const groupText = groupCell?.textContent?.trim().toLowerCase() ?? '';
            row.hidden = query !== '' && !groupText.includes(query);
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initUserListGroupFilter);
} else {
    initUserListGroupFilter();
}
