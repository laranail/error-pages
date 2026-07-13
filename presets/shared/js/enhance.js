/*
 * error-pages progressive enhancement — dependency-free, optional.
 *
 * The branded page is fully functional without JavaScript; this bundle only
 * layers niceties on top (a copy-reference button, a live retry countdown). It
 * is served by the package asset route (assets.mode = "route"). When the visual
 * template set lands, a richer Alpine-driven bundle replaces this file at the
 * same route without any consumer change.
 */
(function () {
  'use strict';

  if (typeof document === 'undefined') {
    return;
  }

  var ready = function (fn) {
    if (document.readyState !== 'loading') {
      fn();
    } else {
      document.addEventListener('DOMContentLoaded', fn);
    }
  };

  ready(function () {
    // Copy the support reference id to the clipboard.
    var ref = document.querySelector('.ep-ref code');
    if (ref && navigator.clipboard) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'ep-copy';
      btn.textContent = 'Copy';
      btn.addEventListener('click', function () {
        navigator.clipboard.writeText((ref.textContent || '').trim()).then(function () {
          var was = btn.textContent;
          btn.textContent = 'Copied';
          window.setTimeout(function () {
            btn.textContent = was;
          }, 2000);
        });
      });
      ref.parentNode.appendChild(btn);
    }

    // Live countdown for a retryable page (a Retry-After meta refresh is set).
    var meta = document.querySelector('meta[http-equiv="refresh" i]');
    var slot = document.querySelector('[data-ep-countdown]');
    if (!meta || !slot) {
      return;
    }
    var seconds = parseInt((meta.getAttribute('content') || '').split(';')[0], 10);
    if (!(seconds > 0)) {
      return;
    }
    var tick = function () {
      slot.textContent = String(seconds);
      if (seconds-- <= 0) {
        window.clearInterval(timer);
      }
    };
    var timer = window.setInterval(tick, 1000);
    tick();
  });
})();
