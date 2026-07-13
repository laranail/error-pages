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
    // The templates need no special markup: if a `[data-ep-countdown]` slot is
    // present it is used, otherwise a subtle retry line is created so the
    // countdown is visible on every stack. The browser still auto-reloads via
    // the meta refresh with or without this.
    var meta = document.querySelector('meta[http-equiv="refresh" i]');
    if (!meta) {
      return;
    }
    var seconds = parseInt((meta.getAttribute('content') || '').split(';')[0], 10);
    if (!(seconds > 0)) {
      return;
    }

    var slot = document.querySelector('[data-ep-countdown]');
    if (!slot) {
      var host = document.querySelector('.ep-card') || document.body;
      var line = document.createElement('p');
      line.className = 'ep-retry';
      slot = document.createElement('span');
      slot.setAttribute('data-ep-countdown', '');
      line.append('Retrying in ', slot, 's…');
      host.appendChild(line);
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
