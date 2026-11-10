/* Keep a standard school-year list and optional text entry on the same form value. */
document.querySelectorAll('[data-school-year-picker]').forEach(picker => {
    const choice = picker.querySelector('[data-school-year-choice]');
    const input = picker.querySelector('[data-school-year-input]');
    const help = picker.querySelector('[data-school-year-help]');

    function validate() {
        const match = /^(\d{4})-(\d{4})$/.exec(input.value);
        const valid = !input.value || (match && Number(match[2]) === Number(match[1]) + 1);
        input.setCustomValidity(valid ? '' : 'Enter consecutive school years, for example 2025-2026.');
    }

    function showCustom(show) {
        input.classList.toggle('d-none', !show);
        help.classList.toggle('d-none', !show);
    }

    function syncFromValue() {
        const listed = [...choice.options].some(option => option.value !== 'custom' && option.value === input.value);
        choice.value = listed ? input.value : 'custom';
        showCustom(!listed);
        validate();
    }

    choice.addEventListener('change', () => {
        const custom = choice.value === 'custom';
        if (!custom) input.value = choice.value;
        showCustom(custom);
        validate();
        if (custom) {
            input.focus();
            input.select();
        }
        input.dispatchEvent(new Event('change', { bubbles: true }));
    });
    input.addEventListener('input', validate);
    input.addEventListener('school-year:set', syncFromValue);
    choice.classList.remove('d-none');
    syncFromValue();
});
