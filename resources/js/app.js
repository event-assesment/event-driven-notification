import './bootstrap';
import flatpickr from 'flatpickr';

const dateInputs = document.querySelectorAll('[data-datepicker]');
if (dateInputs.length > 0) {
    flatpickr(dateInputs, {
        enableTime: true,
        time_24hr: true,
        dateFormat: 'Y-m-d H:i',
        altInput: true,
        altFormat: 'M j, Y H:i',
        altInputClass: 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm',
    });
}
