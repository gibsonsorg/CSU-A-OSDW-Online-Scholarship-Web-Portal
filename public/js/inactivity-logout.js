(function () {
    const script = document.currentScript;
    const warning = document.getElementById('inactivityWarning');
    const message = document.getElementById('inactivityWarningMessage');
    const logoutUrl = script && script.dataset.logoutUrl;

    if (!warning || !message || !logoutUrl || window.inactivityLogoutInitialized) {
        return;
    }

    window.inactivityLogoutInitialized = true;

    const stylesheetUrl = script.dataset.stylesheetUrl;
    if (stylesheetUrl && !document.querySelector('link[data-inactivity-stylesheet]')) {
        const stylesheet = document.createElement('link');
        stylesheet.rel = 'stylesheet';
        stylesheet.href = stylesheetUrl;
        stylesheet.dataset.inactivityStylesheet = 'true';
        document.head.appendChild(stylesheet);
    }

    const idleDuration = 4 * 60 * 1000;
    const warningDuration = 60;
    let idleTimer;
    let countdownTimer;
    let secondsRemaining = warningDuration;
    let loggingOut = false;

    function renderCountdown() {
        const unit = secondsRemaining === 1 ? 'second' : 'seconds';
        message.textContent = `You'll be logged out in ${secondsRemaining} ${unit} due to inactivity. Click anywhere or press a key to stay logged in.`;
    }

    function logout() {
        if (loggingOut) {
            return;
        }

        loggingOut = true;
        const token = document.querySelector('meta[name="csrf-token"]')?.content;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = logoutUrl;
        form.hidden = true;

        if (token) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = token;
            form.appendChild(csrfInput);
        }

        document.body.appendChild(form);
        form.submit();
    }

    function startWarning() {
        secondsRemaining = warningDuration;
        renderCountdown();
        warning.hidden = false;

        countdownTimer = window.setInterval(function () {
            secondsRemaining -= 1;
            if (secondsRemaining <= 0) {
                window.clearInterval(countdownTimer);
                logout();
                return;
            }

            renderCountdown();
        }, 1000);
    }

    function resetTimer() {
        if (loggingOut) {
            return;
        }

        window.clearTimeout(idleTimer);
        window.clearInterval(countdownTimer);
        warning.hidden = true;
        idleTimer = window.setTimeout(startWarning, idleDuration);
    }

    ['mousemove', 'click', 'keydown', 'keypress', 'scroll', 'touchstart'].forEach(function (eventName) {
        document.addEventListener(eventName, resetTimer, { capture: true, passive: true });
    });

    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            window.location.reload();
        }
    });

    resetTimer();
})();