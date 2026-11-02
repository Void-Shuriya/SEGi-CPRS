// Enable only the Study Modes that belong to the selected Programme.
let programmeSelect = document.getElementById('programme_id');
let studyModeSelect = document.getElementById('study_mode_id');

if (programmeSelect && studyModeSelect) {
    function filterStudyModes(clearSelection) {
        let programmeId = programmeSelect.value;
        let allOptions = studyModeSelect.getElementsByTagName('option');

        // Clear Study Mode only when the user changes Programme.
        if (clearSelection) {
            studyModeSelect.value = '';
        }

        // Show matching Study Modes and hide unrelated options.
        for (let i = 0; i < allOptions.length; i++) {
            let option = allOptions[i];
            let optionProgrammeId = option.getAttribute('data-programme-id');

            if (!optionProgrammeId) {
                option.hidden = false;
                option.disabled = false;
            } else if (optionProgrammeId == programmeId) {
                option.hidden = false;
                option.disabled = false;
            } else {
                option.hidden = true;
                option.disabled = true;
            }
        }

        // Keep the Study Mode dropdown disabled until a Programme is selected.
        if (programmeId == '') {
            studyModeSelect.disabled = true;
        } else {
            studyModeSelect.disabled = false;
        }
    }

    programmeSelect.addEventListener('change', function () {
        filterStudyModes(true);
    });

    // Preserve a submitted Study Mode when the form reloads with an error.
    filterStudyModes(false);
}
