(function () {
  'use strict';

  function isMobileLike() {
    return /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent)
      || (navigator.maxTouchPoints > 1 && window.matchMedia('(max-width: 900px)').matches);
  }

  function setupNav() {
    var toggle = document.querySelector('[data-nav-toggle]');
    var nav = document.querySelector('[data-site-nav]');
    if (!toggle || !nav) return;

    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  function setupViewer() {
    var viewer = document.getElementById('product-viewer');
    if (!viewer) return;

    var loading = document.querySelector('[data-viewer-loading]');
    var errorEl = document.querySelector('[data-viewer-error]');
    var help = document.querySelector('[data-ar-help]');

    function hideLoading() {
      if (loading) loading.hidden = true;
    }

    function showError() {
      hideLoading();
      if (errorEl) errorEl.hidden = false;
    }

    viewer.addEventListener('load', function () {
      hideLoading();
      if (errorEl) errorEl.hidden = true;
    });

    viewer.addEventListener('error', function () {
      showError();
    });

    viewer.addEventListener('ar-status', function (event) {
      var status = event.detail && event.detail.status;
      if (!help) return;
      if (status === 'failed') {
        help.querySelector('p').textContent =
          'AR could not start on this device/browser. Try Chrome on Android or Safari on iPhone/iPad.';
      }
      if (status === 'not-presenting' || status === 'presenting') {
        // Recover quietly when returning from AR
        if (viewer.loaded) {
          hideLoading();
        }
      }
    });

    function recoverViewer() {
      if (!viewer || !viewer.src) return;
      try {
        if (typeof viewer.dismissPoster === 'function') {
          viewer.dismissPoster();
        }
      } catch (e) {
        /* ignore */
      }
    }

    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'visible') {
        recoverViewer();
      }
    });

    window.addEventListener('pageshow', function () {
      recoverViewer();
    });

    // Large GLBs can take a while on local Wi‑Fi — do not flash a false error too early.
    window.setTimeout(function () {
      if (loading && !loading.hidden && !viewer.loaded) {
        if (loading) {
          loading.textContent = 'Still loading 3D model… large files can take a minute.';
        }
      }
    }, 20000);

    window.setTimeout(function () {
      if (loading && !loading.hidden && !viewer.loaded) {
        showError();
      }
    }, 120000);
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (isMobileLike()) {
      document.body.classList.add('is-mobile');
    }
    setupNav();
    setupViewer();
  });
})();
