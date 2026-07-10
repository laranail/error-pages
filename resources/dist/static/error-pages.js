/**
 * Server Error Pages — tiny, dependency-free enhancement script.
 * Inlined into every page. Everything degrades gracefully without JS:
 * links work, and retryable pages still reload via <meta http-equiv="refresh">.
 */
(function () {
  "use strict";

  var cfg = window.__sep || {};

  function onReady(fn) {
    if (document.readyState !== "loading") {
      fn();
    } else {
      document.addEventListener("DOMContentLoaded", fn);
    }
  }

  function fallbackCopy(text, done) {
    try {
      var ta = document.createElement("textarea");
      ta.value = text;
      ta.setAttribute("readonly", "");
      ta.style.position = "absolute";
      ta.style.left = "-9999px";
      document.body.appendChild(ta);
      ta.select();
      document.execCommand("copy");
      document.body.removeChild(ta);
      done();
    } catch (e) {
      /* clipboard unavailable — no-op */
    }
  }

  onReady(function () {
    document.querySelectorAll('[data-sep-action="copy"]').forEach(function (btn) {
      btn.addEventListener("click", function () {
        var code = btn.getAttribute("data-sep-code") || "";
        var title = btn.getAttribute("data-sep-title") || "";
        var details =
          "Error " + code + (title ? " — " + title : "") +
          "\nURL: " + location.href +
          "\nTime: " + new Date().toISOString();
        var original = btn.textContent;
        var done = function () {
          btn.textContent = "Copied";
          btn.classList.add("is-copied");
          setTimeout(function () {
            btn.textContent = original;
            btn.classList.remove("is-copied");
          }, 1600);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(details).then(done).catch(function () {
            fallbackCopy(details, done);
          });
        } else {
          fallbackCopy(details, done);
        }
      });
    });

    document.querySelectorAll('[data-sep-action="retry"]').forEach(function (btn) {
      btn.addEventListener("click", function () {
        location.reload();
      });
    });

    var counter = document.querySelector("[data-sep-countdown]");
    if (counter && cfg.retryable) {
      var secs = parseInt(counter.getAttribute("data-sep-countdown"), 10);
      if (secs > 0) {
        counter.hidden = false;
        var span = counter.querySelector("[data-sep-seconds]");
        var tick = setInterval(function () {
          secs -= 1;
          if (span) {
            span.textContent = String(Math.max(secs, 0));
          }
          if (secs <= 0) {
            clearInterval(tick);
            window.location.assign(cfg.urlBase || "/");
          }
        }, 1000);
      }
    }
  });
})();
