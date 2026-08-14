@php
    $theme = $theme ?? null;
@endphp
<script id="admin-theme-script">
(function () {
    "use strict";
    var key = "admin-theme";
    var stored = null;
    try { stored = localStorage.getItem(key); } catch (e) {}
    var theme = stored === "light" || stored === "dark"
        ? stored
        : (window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light");
    if (theme === "light") { document.documentElement.classList.add("light-theme"); }
    else { document.documentElement.classList.remove("light-theme"); }
})();
</script>
