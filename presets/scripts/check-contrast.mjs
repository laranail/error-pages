// WCAG 2.1 AA contrast gate for the theme tokens. Reads the BUILT
// shared/css/critical.css, extracts each preset's --ep-* custom properties
// (light + dark), and asserts every text/background pair meets AA. Exits 1 on
// any failure so `npm run check:contrast` guards the design against regressions.
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const css = readFileSync(
  join(dirname(fileURLToPath(import.meta.url)), '../shared/css/critical.css'),
  'utf8',
);
const presets = ['default', 'slate', 'midnight', 'emerald', 'crimson'];

const channel = (c) => {
  c /= 255;
  return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
};
const luminance = (hex) => {
  let h = hex.replace('#', '');
  if (h.length === 3)
    h = h
      .split('')
      .map((x) => x + x)
      .join('');
  const r = parseInt(h.slice(0, 2), 16),
    g = parseInt(h.slice(2, 4), 16),
    b = parseInt(h.slice(4, 6), 16);
  return 0.2126 * channel(r) + 0.7152 * channel(g) + 0.0722 * channel(b);
};
const ratio = (a, b) => {
  const l1 = luminance(a),
    l2 = luminance(b);
  return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
};

const vars = (block) => {
  const out = {};
  for (const m of block.matchAll(/--ep-([a-z0-9-]+):\s*(#[0-9a-fA-F]{3,6})/g)) out[m[1]] = m[2];
  return out;
};
const light = (name) =>
  vars((css.match(new RegExp(`(?:^|\\s)\\.ep-theme-${name}\\s*\\{([^}]*)\\}`, 'm')) || [, ''])[1]);
const dark = (name) =>
  vars((css.match(new RegExp(`\\.ep-auto-dark\\.ep-theme-${name}\\s*\\{([^}]*)\\}`)) || [, ''])[1]);

// [label, foreground token, background token, minimum ratio]
const pairs = [
  ['title  text/surface', 'text', 'surface', 4.5],
  ['muted  muted/surface', 'muted', 'surface', 4.5],
  ['status accent/surface', 'accent', 'surface', 3.0],
  ['button on-accent/accent', 'on-accent', 'accent', 4.5],
];

let failures = 0;
for (const name of presets) {
  for (const [mode, t] of [
    ['light', light(name)],
    ['dark', dark(name)],
  ]) {
    for (const [label, fg, bg, min] of pairs) {
      if (!t[fg] || !t[bg]) {
        console.error(`MISSING ${name}/${mode} ${label} (${fg} or ${bg})`);
        failures++;
        continue;
      }
      const r = ratio(t[fg], t[bg]);
      if (r < min) {
        console.error(
          `FAIL ${name}/${mode} ${label} ${r.toFixed(2)} < ${min} (${t[fg]} on ${t[bg]})`,
        );
        failures++;
      }
    }
  }
}

if (failures > 0) {
  console.error(`\n${failures} WCAG AA contrast failure(s).`);
  process.exit(1);
}
console.log(`WCAG AA OK — all ${presets.length} themes (light + dark) pass.`);
