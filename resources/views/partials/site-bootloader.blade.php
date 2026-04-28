@php
    $bootLogoUrl = trim((string) ($site['site_logo_url'] ?? ''));
    $bootSiteName = trim((string) ($site['site_name_resolved'] ?? '')) !== '' ? $site['site_name_resolved'] : ($site['site_name'] ?? config('app.name'));
@endphp
<div id="site-bootloader"
     class="site-bootloader"
     role="status"
     aria-live="polite"
     aria-busy="true"
     aria-label="{{ __('site.bootloader.loading') }}">
    <div class="site-bootloader__veil" aria-hidden="true"></div>
    <div class="site-bootloader__stage">
        <div class="site-bootloader__orbit" aria-hidden="true">
            <div class="site-bootloader__comet">
                <span class="site-bootloader__comet-trail"></span>
                <span class="site-bootloader__comet-head"></span>
            </div>
        </div>
        <div class="site-bootloader__logo">
            @if ($bootLogoUrl !== '')
                <img src="{{ $bootLogoUrl }}" alt="{{ $bootSiteName }}" width="72" height="72" decoding="async" fetchpriority="high" />
            @else
                <svg viewBox="0 0 24 24" class="site-bootloader__logo-fallback" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M12 3L2 9l10 6 10-6-10-6Z" stroke="currentColor" stroke-width="1.7" />
                    <path d="M2 9v8l10 6 10-6V9" stroke="currentColor" stroke-width="1.7" opacity=".85"/>
                </svg>
            @endif
        </div>
    </div>
</div>
<script>
    (function () {
        function hide() {
            var el = document.getElementById('site-bootloader');
            if (!el || el.dataset.done === '1') {
                return;
            }
            el.dataset.done = '1';
            el.setAttribute('aria-busy', 'false');
            el.classList.add('site-bootloader--out');
            document.body.classList.remove('site-bootloader-active');
            function remove() {
                if (el && el.parentNode) {
                    el.parentNode.removeChild(el);
                }
            }
            el.addEventListener('transitionend', function (e) {
                if (e.propertyName === 'opacity') {
                    remove();
                }
            }, { once: true });
            setTimeout(remove, 700);
        }
        if (document.readyState === 'complete') {
            requestAnimationFrame(function () { requestAnimationFrame(hide); });
        } else {
            window.addEventListener('load', function () {
                requestAnimationFrame(function () { requestAnimationFrame(hide); });
            }, { once: true });
        }
        setTimeout(hide, 12000);
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
                hide();
            }
        });
    })();
</script>
