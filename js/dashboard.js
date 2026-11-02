(() => {
    const print = document.getElementById('print-report');
    if (print) print.addEventListener('click', () => window.print());
    const form = document.getElementById('dashboard-filters');
    if (!form) return;
    const search = document.getElementById('result-search');
    const status = document.getElementById('status-filter');
    const sort = document.getElementById('result-sort');
    const body = document.querySelector('#results-table tbody');
    const rows = [...body.querySelectorAll('[data-result-row]')];
    const empty = document.getElementById('results-empty');
    const count = document.getElementById('result-count');

    function update() {
        const term = search.value.trim().toLowerCase();
        rows.sort((a, b) => {
            let order = 0;
            if (sort.value === 'name')
                order = a.dataset.name.localeCompare(b.dataset.name, undefined, { numeric: true, sensitivity: 'base' });
            if (sort.value === 'mark_desc') order = Number(b.dataset.mark) - Number(a.dataset.mark);
            if (sort.value === 'mark_asc') order = Number(a.dataset.mark) - Number(b.dataset.mark);
            if (sort.value === 'attendance') order = Number(a.dataset.attendance) - Number(b.dataset.attendance);
            return order || a.dataset.id.localeCompare(b.dataset.id, undefined, { numeric: true });
        });
        let visible = 0;
        rows.forEach((row) => {
            row.hidden =
                (status.value !== '' && row.dataset.status !== status.value) ||
                !(row.dataset.name + ' ' + row.dataset.id).toLowerCase().includes(term);
            if (!row.hidden) visible++;
            body.append(row);
        });
        body.append(empty);
        empty.hidden = visible !== 0;
        count.textContent = 'Showing ' + visible + ' of ' + rows.length + ' students';
    }
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        update();
    });
    document.getElementById('reset-results').addEventListener('click', () => {
        search.value = '';
        status.value = '';
        sort.value = 'name';
        update();
        search.focus();
    });
    search.addEventListener('input', update);
    status.addEventListener('change', update);
    sort.addEventListener('change', update);
    document.querySelectorAll('.status-metric').forEach((card) => {
        card.addEventListener('click', (event) => {
            event.preventDefault();
            status.value = card.dataset.status;
            search.value = '';
            update();
            document.getElementById('results-title').focus({ preventScroll: true });
            document.getElementById('student-results').scrollIntoView({ block: 'start' });
        });
    });
    update();
})();
