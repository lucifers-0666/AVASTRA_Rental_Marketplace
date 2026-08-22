// SpaceShare — global JS helpers

// Flatpickr: init any input marked with data-datepicker
document.addEventListener('DOMContentLoaded', () => {
  if (window.flatpickr) {
    flatpickr('[data-datepicker]', { dateFormat: 'Y-m-d', minDate: 'today' });
  }
});

// Small fetch helper for the REST-style endpoints in /api
async function api(path, options = {}) {
  const res = await fetch(path, {
    headers: { 'X-Requested-With': 'XMLHttpRequest', ...options.headers },
    ...options,
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || `Request failed (${res.status})`);
  return data;
}
