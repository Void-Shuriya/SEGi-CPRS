(() => {
    const intake = document.getElementById('intake-filter');
    const search = document.getElementById('student-search');
    const selectAll = document.getElementById('select-all-students');
    if (!intake || !search || !selectAll) return;
    const groups = [...document.querySelectorAll('.intake-group')];
    const choices = [...document.querySelectorAll('.student-choice')];
    const empty = document.getElementById('students-empty');

    function updateSelection() {
        const visible = choices.filter((choice) => !choice.hidden);
        const selected = choices.filter((choice) => choice.querySelector('input').checked);
        const allVisibleSelected =
            visible.length > 0 && visible.every((choice) => choice.querySelector('input').checked);
        selectAll.checked = allVisibleSelected || (visible.length === 0 && selected.length > 0);
        selectAll.indeterminate = selected.length > 0 && !selectAll.checked;
        selectAll.disabled = visible.length === 0 && selected.length === 0;
    }

    function filter() {
        const term = search.value.trim().toLowerCase();
        groups.forEach((group) => {
            const matches = intake.value === '' || group.dataset.intake === intake.value;
            group.querySelectorAll('.student-choice').forEach((choice) => {
                choice.hidden = !matches || !choice.textContent.toLowerCase().includes(term);
            });
            group.hidden = ![...group.querySelectorAll('.student-choice')].some((choice) => !choice.hidden);
        });
        empty.hidden = choices.some((choice) => !choice.hidden);
        updateSelection();
    }

    selectAll.addEventListener('change', () => {
        // Select the filtered list; unchecking clears every selection, including hidden ones.
        choices.forEach((choice) => {
            if (!selectAll.checked || !choice.hidden) choice.querySelector('input').checked = selectAll.checked;
        });
        updateSelection();
    });
    [intake, search].forEach((input) => input.addEventListener('input', filter));
    choices.forEach((choice) => choice.querySelector('input').addEventListener('change', updateSelection));
    filter();
})();
