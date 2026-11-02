// Preview the class average for each CLO using valid marks currently in the form.
// This changes only the display; the server saves marks when Save Marks is submitted.
function updateAttainmentPreview() {
    for (let clo = 1; clo <= 3; clo++) {
        const card = document.querySelector('.attainment-card[data-clo="' + clo + '"]');
        if (!card) continue;
        const inputs = [...document.querySelectorAll('.student-row .raw' + clo)];
        // Keep this CLO's previous preview while an entry is blank or invalid.
        if (inputs.some((input) => input.value === '' || !input.validity.valid)) continue;
        const total = inputs.reduce((sum, input) => sum + Number(input.value), 0);
        const average = inputs.length ? total / inputs.length : 0;
        const bar = card.querySelector('.attainment-bar');
        bar.style.width = average.toFixed(2) + '%';
        bar.classList.toggle('attainment-high', average >= 50);
        bar.classList.toggle('attainment-low', average < 50);
        card.querySelector('.attainment-value').textContent = average.toFixed(2) + '%';
    }
}

// Determine whether a coursework or examination component is passed.
// Keep these preview rules aligned with includes/functions.php, which calculates saved reports.
function getComponentResult(earned, available) {
    // Return P when at least half of the available component marks were earned.
    if (available == 0) return 'P';
    if (earned >= available / 2) return 'P';

    return 'F';
}

// Determine the academic status from the coursework and examination results.
function getStatus(cwResult, exResult) {
    // Coursework failure has priority and requires a Retake.
    if (cwResult == 'F') return 'Retake';

    // Examination failure requires a Resit.
    if (exResult == 'F') return 'Resit';

    return 'Pass';
}

// Assign a grade from the calculated final mark.
function getGrade(mark) {
    if (mark >= 80) return 'A';
    if (mark >= 75) return 'A-';
    if (mark >= 70) return 'B+';
    if (mark >= 65) return 'B';
    if (mark >= 60) return 'B-';
    if (mark >= 55) return 'C+';
    if (mark >= 50) return 'C';
    if (mark >= 45) return 'C-';
    if (mark >= 40) return 'D+';
    if (mark >= 35) return 'D';

    return 'F';
}

// Recalculate one student's performance when a raw mark changes.
function calculateRow(inputElement) {
    updateAttainmentPreview();

    // Find the table row containing the changed input.
    let row = inputElement.closest('tr');

    // Preserve invalid input for correction; do not silently clamp marks.
    if ([...row.querySelectorAll('.mark-input')].some((input) => !input.validity.valid)) return;
    // Retrieve the three CLO weightages stored in the row.
    let weight1 = parseFloat(row.getAttribute('data-w1')) || 0;
    let weight2 = parseFloat(row.getAttribute('data-w2')) || 0;
    let weight3 = parseFloat(row.getAttribute('data-w3')) || 0;

    // Retrieve the student's raw marks from the row.
    let raw1 = parseFloat(row.querySelector('.raw1').value) || 0;
    let raw2 = parseFloat(row.querySelector('.raw2').value) || 0;
    let raw3 = parseFloat(row.querySelector('.raw3').value) || 0;

    // Convert each raw mark into its weighted course mark.
    let weighted1 = (raw1 / 100) * weight1;
    let weighted2 = (raw2 / 100) * weight2;
    let weighted3 = (raw3 / 100) * weight3;

    // Display the calculated weighted marks using two decimal places.
    row.querySelector('.wtd1').innerText = weighted1.toFixed(2);
    row.querySelector('.wtd2').innerText = weighted2.toFixed(2);
    row.querySelector('.wtd3').innerText = weighted3.toFixed(2);

    // Calculate and display the student's final mark.
    let total = weighted1 + weighted2 + weighted3;
    row.querySelector('.total-mark').innerText = total.toFixed(2);

    // Assign and display the grade based on the final mark.
    row.querySelector('.grade-text').innerText = getGrade(total);

    // CLO1 and CLO2 represent coursework, while CLO3 represents examination.
    let cwResult = getComponentResult(weighted1 + weighted2, weight1 + weight2);

    let exResult = getComponentResult(weighted3, weight3);
    let status = getStatus(cwResult, exResult);

    // Update the component results and the student's status badge.
    showComponentResult(row.querySelector('.cw-result'), cwResult);

    showComponentResult(row.querySelector('.ex-result'), exResult);

    row.querySelector('.status-container').innerHTML =
        '<span class="badge badge-' + status.toLowerCase() + '">' + status + '</span>';
}

// Display P in green and F in red.
function showComponentResult(cell, result) {
    cell.innerText = result;

    // Remove the previous result colour before applying the new one.
    cell.classList.remove('component-p', 'component-f');
    cell.classList.add('component-' + result.toLowerCase());
}

(() => {
    const weights = [...document.querySelectorAll('[data-weight]')];
    const marksForm = document.getElementById('marks-form');
    const fieldset = marksForm.querySelector('fieldset');
    const save = document.getElementById('save-marks');
    const savedWeights = marksForm.querySelector('[name="saved_weights"]').value.split(',').map(Number);

    function updateMarksAccess() {
        const sum = weights.reduce((total, input) => total + (Number(input.value) || 0), 0);
        const valid = sum === 100 && weights.every((input) => input.value !== '' && input.validity.valid);
        // Marks use saved weights. Save a changed allocation before editing marks.
        const ready = valid && weights.every((input, i) => Number(input.value) === savedWeights[i]);
        fieldset.disabled = !ready;
        if (save) save.disabled = !ready;
        document.querySelector('.marks-table').classList.toggle('marks-table-disabled', !ready);
    }

    // Keep entered percentages within range, including pasted values.
    document.querySelectorAll('[data-weight], .mark-input, .attendance-input').forEach((input) => {
        input.addEventListener('input', () => {
            if (input.value !== '' && Number.isFinite(Number(input.value))) {
                if (Number(input.value) > 100) input.value = '100';
                if (Number(input.value) < 0) input.value = '0';
            }
            if (input.hasAttribute('data-weight')) updateMarksAccess();
            else if (input.classList.contains('mark-input')) calculateRow(input);
        });
    });
    updateMarksAccess();
    document.querySelectorAll('.student-row .raw1').forEach((input) => calculateRow(input));
})();
