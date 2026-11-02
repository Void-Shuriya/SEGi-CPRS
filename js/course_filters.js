(() => {
    const section = document.querySelector('[data-course-list]');
    if (!section) return;
    const search = document.getElementById('course-search');
    const programme = document.getElementById('course-programme');
    const mode = document.getElementById('course-mode');
    const lecturer = document.getElementById('course-lecturer');
    const rows = [...section.querySelectorAll('[data-course-row]')];
    const count = document.getElementById('course-count');
    const empty = document.getElementById('course-empty');
    function populate(select, key) {
        if (!select) return;
        [...new Set(rows.map((row) => row.dataset[key]))].sort().forEach((value) => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = value;
            select.append(option);
        });
    }
    populate(programme, 'programme');
    populate(lecturer, 'lecturer');
    populate(mode, 'mode');
    function filter() {
        let visible = 0;
        rows.forEach((row) => {
            row.hidden =
                !row.dataset.search.includes(search.value.trim().toLowerCase()) ||
                (programme && programme.value !== '' && row.dataset.programme !== programme.value) ||
                (lecturer && lecturer.value !== '' && row.dataset.lecturer !== lecturer.value) ||
                (mode && mode.value !== '' && row.dataset.mode !== mode.value);
            if (!row.hidden) visible++;
        });
        count.textContent = 'Showing ' + visible + ' of ' + rows.length + ' courses';
        if (empty) empty.hidden = visible !== 0;
    }
    [search, programme, lecturer, mode].filter(Boolean).forEach((input) => input.addEventListener('input', filter));
    document.getElementById('reset-courses').addEventListener('click', () => {
        search.value = '';
        if (programme) programme.value = '';
        if (lecturer) lecturer.value = '';
        if (mode) mode.value = '';
        filter();
        search.focus();
    });
    filter();
})();
