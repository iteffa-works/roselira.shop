(function () {
    'use strict';

    if (window.__FLOWAXY_VISITOR_TRACK__) {
        return;
    }
    window.__FLOWAXY_VISITOR_TRACK__ = true;

    var ENDPOINT = '/track';
    var SESSION_KEY = 'flowaxy_visitor_sid';
    var SCROLL_SENT = {};
    var startedAt = Date.now();
    var queue = [];
    var flushTimer = null;

    function sessionId() {
        try {
            var existing = sessionStorage.getItem(SESSION_KEY);
            if (existing) {
                return existing;
            }
            var id = (window.crypto && crypto.randomUUID)
                ? crypto.randomUUID()
                : ('v-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 10));
            sessionStorage.setItem(SESSION_KEY, id);
            return id;
        } catch (error) {
            return 'v-fallback-' + Date.now();
        }
    }

    function path() {
        return window.location.pathname || '/';
    }

    function shouldTrack() {
        var p = path();
        return p.indexOf('/admin') !== 0;
    }

    function pct(value, total) {
        if (!total || total <= 0) {
            return 0;
        }
        return Math.max(0, Math.min(100, Math.round((value / total) * 1000) / 10));
    }

    function pushEvent(event) {
        if (!shouldTrack()) {
            return;
        }
        event.path = event.path || path();
        queue.push(event);
        if (queue.length >= 12) {
            flush(false);
        } else {
            scheduleFlush();
        }
    }

    function scheduleFlush() {
        if (flushTimer) {
            return;
        }
        flushTimer = window.setTimeout(function () {
            flushTimer = null;
            flush(false);
        }, 2500);
    }

    function payload(extra) {
        return Object.assign({
            session_id: sessionId(),
            referrer: document.referrer || '',
            landing_path: path(),
            locale: document.documentElement.lang || '',
            screen_w: window.screen ? window.screen.width : 0,
            screen_h: window.screen ? window.screen.height : 0,
            viewport_w: window.innerWidth || 0,
            viewport_h: window.innerHeight || 0,
            events: queue.splice(0, queue.length),
        }, extra || {});
    }

    function send(data, useBeacon) {
        if (!data.events || data.events.length === 0) {
            return;
        }

        var body = JSON.stringify(data);
        if (useBeacon && navigator.sendBeacon) {
            navigator.sendBeacon(ENDPOINT, new Blob([body], { type: 'application/json' }));
            return;
        }

        fetch(ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: body,
            keepalive: true,
            credentials: 'same-origin',
        }).catch(function () { /* ignore */ });
    }

    function flush(useBeacon) {
        if (queue.length === 0) {
            return;
        }
        send(payload(), useBeacon);
    }

    function trackPageview() {
        pushEvent({ type: 'pageview' });
    }

    function trackClick(event) {
        var doc = document.documentElement;
        var body = document.body;
        var scrollX = window.pageXOffset || doc.scrollLeft || 0;
        var scrollY = window.pageYOffset || doc.scrollTop || 0;
        var docW = Math.max(doc.scrollWidth, body ? body.scrollWidth : 0, doc.clientWidth);
        var docH = Math.max(doc.scrollHeight, body ? body.scrollHeight : 0, doc.clientHeight);
        var x = event.pageX ?? (event.clientX + scrollX);
        var y = event.pageY ?? (event.clientY + scrollY);
        var target = event.target && event.target.closest ? event.target.closest('a,button,[role="button"],input,select,textarea,.product-card,.site-logo') : event.target;

        pushEvent({
            type: 'click',
            x_pct: pct(x, docW),
            y_pct: pct(y, docH),
            tag: target && target.tagName ? target.tagName : '',
        });
    }

    function trackScroll() {
        var doc = document.documentElement;
        var body = document.body;
        var docH = Math.max(doc.scrollHeight, body ? body.scrollHeight : 0, 1);
        var viewH = window.innerHeight || doc.clientHeight || 0;
        var scrollY = window.pageYOffset || doc.scrollTop || 0;
        var depth = pct(scrollY + viewH, docH);
        var marks = [25, 50, 75, 100];

        marks.forEach(function (mark) {
            var key = path() + ':' + mark;
            if (depth >= mark && !SCROLL_SENT[key]) {
                SCROLL_SENT[key] = true;
                pushEvent({ type: 'scroll', scroll_pct: mark });
            }
        });
    }

    function trackLeave() {
        var duration = Math.round((Date.now() - startedAt) / 1000);
        queue.push({ type: 'leave', path: path(), duration_sec: duration });
        flush(true);
    }

    if (!shouldTrack()) {
        return;
    }

    trackPageview();

    document.addEventListener('click', function (event) {
        trackClick(event);
    }, { passive: true });

    window.addEventListener('scroll', function () {
        trackScroll();
    }, { passive: true });

    window.addEventListener('pagehide', trackLeave);
    window.addEventListener('beforeunload', trackLeave);

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            trackLeave();
        }
    });
})();
