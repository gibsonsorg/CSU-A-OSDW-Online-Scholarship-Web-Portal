<div id="inactivityWarning" class="inactivity-warning" hidden role="alertdialog" aria-modal="true" aria-labelledby="inactivityWarningTitle" aria-describedby="inactivityWarningMessage">
    <div class="inactivity-warning__dialog">
        <h2 id="inactivityWarningTitle">Still there?</h2>
        <p id="inactivityWarningMessage"></p>
    </div>
</div>
<script
    src="{{ asset('js/inactivity-logout.js') }}"
    data-logout-url="{{ route('logout') }}"
    data-stylesheet-url="{{ asset('css/inactivity-logout.css') }}"
    defer
></script>