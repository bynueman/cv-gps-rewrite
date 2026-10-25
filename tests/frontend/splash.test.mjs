import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { test } from 'node:test';
import vm from 'node:vm';

const view = await readFile(new URL('../../resources/views/partials/splash.blade.php', import.meta.url), 'utf8');
const script = view.match(/<script id="gpsfood-splash-controller">([\s\S]*?)<\/script>/)[1];

function documentLoad({ reduced = false, cached = true } = {}) {
  let clock = 0;
  let sequence = 0;
  let removals = 0;
  const timers = new Map();
  const classes = () => {
    const values = new Set();
    return { add: value => values.add(value), contains: value => values.has(value) };
  };
  const logo = Object.assign(new EventTarget(), { complete: cached, naturalWidth: cached ? 3236 : 0, classList: classes() });
  const splash = { hidden: true, classList: classes(), querySelector: () => logo, remove: () => { removals++; } };
  const window = Object.assign(new EventTarget(), {
    matchMedia: () => ({ matches: reduced }),
    setTimeout: (callback, delay = 0) => { const id = ++sequence; timers.set(id, { callback, at: clock + delay }); return id; },
    clearTimeout: id => timers.delete(id),
  });
  vm.runInNewContext(script, { window, document: { getElementById: () => splash }, performance: { now: () => clock } });
  return {
    splash, logo,
    ready: () => window.dispatchEvent(new Event('gpsfood:ready')),
    get removals() { return removals; },
    advance(milliseconds) {
      const target = clock + milliseconds;
      while (true) {
        const next = [...timers].filter(([, timer]) => timer.at <= target).sort((a, b) => a[1].at - b[1].at || a[0] - b[0])[0];
        if (!next) break;
        timers.delete(next[0]); clock = next[1].at; next[1].callback();
      }
      clock = target;
    },
  };
}

test('fast readiness finishes reveal and brief hold before fading out', () => {
  const page = documentLoad();
  assert.equal(page.splash.hidden, false);
  assert.equal(page.logo.classList.contains('logo-ready'), true);
  page.ready(); page.advance(399);
  assert.equal(page.splash.classList.contains('is-leaving'), false);
  page.advance(1);
  assert.equal(page.splash.classList.contains('is-leaving'), true);
  page.advance(219); assert.equal(page.removals, 0);
  page.advance(1); assert.equal(page.removals, 1);
});

test('a slow React boot gets no extra hold when the logo has already been shown', () => {
  const page = documentLoad(); page.advance(2000); page.ready(); page.advance(0);
  assert.equal(page.splash.classList.contains('is-leaving'), true);
  page.advance(220); assert.equal(page.removals, 1);
});

test('reduced motion dismisses without reveal hold or fade delay', () => {
  const page = documentLoad({ reduced: true }); page.ready(); page.advance(0);
  assert.equal(page.removals, 1);
});

test('failed application initialization cannot leave the splash blocking', () => {
  const page = documentLoad(); page.advance(4999); assert.equal(page.removals, 0);
  page.advance(1); assert.equal(page.removals, 1);
  page.ready(); page.advance(1000); assert.equal(page.removals, 1);
});

test('a late image gets its reveal, but a stalled image is bounded by the fail-safe', () => {
  const page = documentLoad({ cached: false }); page.ready(); page.advance(800);
  assert.equal(page.splash.classList.contains('is-leaving'), false);
  page.logo.naturalWidth = 3236; page.logo.dispatchEvent(new Event('load'));
  page.advance(400); assert.equal(page.splash.classList.contains('is-leaving'), true);
  page.advance(220); assert.equal(page.removals, 1);
  const stalled = documentLoad({ cached: false }); stalled.ready(); stalled.advance(5000);
  assert.equal(stalled.removals, 1);
});

test('a failed logo does not prevent an already-ready application from appearing', () => {
  const page = documentLoad({ cached: false }); page.ready();
  page.logo.dispatchEvent(new Event('error')); page.advance(0);
  assert.equal(page.logo.classList.contains('logo-ready'), false);
  assert.equal(page.splash.classList.contains('is-leaving'), true);
  page.advance(220); assert.equal(page.removals, 1);
});

test('repeat readiness cannot duplicate or reopen a splash; a new document starts afresh', () => {
  const first = documentLoad(); first.ready(); first.ready(); first.advance(620);
  first.ready(); first.advance(5000); assert.equal(first.removals, 1);
  const refreshed = documentLoad(); assert.equal(refreshed.splash.hidden, false);
  assert.equal(refreshed.removals, 0);
});
