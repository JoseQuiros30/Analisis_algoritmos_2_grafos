const form = document.querySelector('#route-planner-form');

if (form) {
    const origin = form.querySelector('#origin_station_id');
    const destination = form.querySelector('#destination_station_id');
    const swap = form.querySelector('#swap-stations');
    const summary = document.querySelector('#validated-selection');
    const results = document.querySelector('#route-results');
    const changed = document.querySelector('#selection-changed');

    function invalidateSummary() {
        if (results) {
            results.hidden = true;
        }
        if (summary) {
            summary.hidden = true;
            changed.hidden = false;
        }
    }

    swap.hidden = false;
    swap.addEventListener('click', () => {
        [origin.value, destination.value] = [destination.value, origin.value];
        invalidateSummary();
        origin.focus();
    });
    form.addEventListener('input', invalidateSummary);
    form.addEventListener('change', invalidateSummary);
}
