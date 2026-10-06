(function () {
  'use strict';

  function isMobileLike() {
    return /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);
  }

  function isLoopbackUrl(url) {
    try {
      var host = new URL(url, window.location.href).hostname.toLowerCase();
      return host === 'localhost' || host === '127.0.0.1' || host === '::1';
    } catch (e) {
      return false;
    }
  }

  function renderQr(el, url) {
    var src = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&margin=8&data='
      + encodeURIComponent(url);
    var img = document.createElement('img');
    img.src = src;
    img.alt = 'QR code to open AR furniture page on phone';
    img.width = 180;
    img.height = 180;
    img.loading = 'lazy';
    el.replaceChildren(img);
  }

  document.addEventListener('DOMContentLoaded', function () {
    if (isMobileLike()) {
      document.querySelectorAll('[data-qr-panel]').forEach(function (panel) {
        panel.hidden = true;
      });
      return;
    }

    document.querySelectorAll('[data-qr-code]').forEach(function (el) {
      var url = el.getAttribute('data-qr-url') || window.location.href;

      // Never encode localhost into QR when a better absolute URL is already provided.
      // If still loopback, keep it but the PHP page shows a config warning.
      if (!url || isLoopbackUrl(url)) {
        // Prefer current location if user opened via LAN IP (e.g. 192.168.x.x)
        if (!isLoopbackUrl(window.location.href)) {
          url = window.location.href;
        }
      }

      renderQr(el, url);

      var text = el.closest('[data-qr-panel]') && el.closest('[data-qr-panel]').querySelector('[data-qr-url-text]');
      if (text && url) {
        text.textContent = url;
      }
    });
  });
})();
