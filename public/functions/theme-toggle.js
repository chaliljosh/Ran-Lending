document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('themeToggleBtn');
    if (!btn) return;

    btn.addEventListener('click', function () {
        var current = document.documentElement.getAttribute('data-theme') || 'light';
        var next = current === 'dark' ? 'light' : 'dark';

        document.documentElement.setAttribute('data-theme', next);

        try {
            localStorage.setItem('ran-theme', next);
        } catch (err) {
            // localStorage unavailable (private mode, atbp.) — hindi
            // kritikal, magtutuloy lang ang toggle nang walang persistence.
        }
    });
});