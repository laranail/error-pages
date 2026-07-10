/**
 * Server Error Pages — tiny, dependency-free enhancement script.
 * Built by Vite → public/assets/js/error-pages.js and linked (or inlined for the
 * standalone export). Config is read from <body> data-* attributes, so there is
 * no inline config script. Everything degrades gracefully without JS: links
 * work, and retryable pages still reload via <meta http-equiv="refresh">.
 */
import "../scss/error-pages.scss";

(function () {
  "use strict";

  function onReady(fn) {
    if (document.readyState !== "loading") {
      fn();
    } else {
      document.addEventListener("DOMContentLoaded", fn);
    }
  }

  function fallbackCopy(text, done) {
    try {
      const ta = document.createElement("textarea");
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
    const data = document.body.dataset;
    const urlBase = data.sepUrlBase || "/";
    const retryable = data.sepRetryable === "1";

    document.querySelectorAll('[data-sep-action="copy"]').forEach(function (btn) {
      btn.addEventListener("click", function () {
        const code = btn.getAttribute("data-sep-code") || "";
        const title = btn.getAttribute("data-sep-title") || "";
        const details =
          "Error " + code + (title ? " — " + title : "") +
          "\nURL: " + location.href +
          "\nTime: " + new Date().toISOString();
        const original = btn.textContent;
        const done = function () {
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

    const counter = document.querySelector("[data-sep-countdown]");
    if (counter && retryable) {
      let secs = parseInt(counter.getAttribute("data-sep-countdown"), 10);
      if (secs > 0) {
        counter.hidden = false;
        const span = counter.querySelector("[data-sep-seconds]");
        const tick = setInterval(function () {
          secs -= 1;
          if (span) {
            span.textContent = String(Math.max(secs, 0));
          }
          if (secs <= 0) {
            clearInterval(tick);
            window.location.assign(urlBase);
          }
        }, 1000);
      }
    }
  });
})();
