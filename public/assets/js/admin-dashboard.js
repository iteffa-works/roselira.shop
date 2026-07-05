(function () {
    'use strict';

    function parseHeatmap(root) {
        var scriptId = root.getAttribute('data-heatmap-id');
        if (scriptId) {
            var node = document.getElementById(scriptId);
            if (node && node.textContent) {
                try {
                    var parsed = JSON.parse(node.textContent);
                    if (Array.isArray(parsed)) {
                        return parsed;
                    }
                } catch (error) {
                    /* fallback below */
                }
            }
        }

        var raw = root.getAttribute('data-heatmap') || '[]';
        try {
            return JSON.parse(raw);
        } catch (error) {
            return [];
        }
    }

    function pagePreviewUrl(path) {
        var normalized = path && path.charAt(0) === '/' ? path : '/' + (path || '');
        var joiner = normalized.indexOf('?') >= 0 ? '&' : '?';
        return normalized + joiner + 'heatmap_preview=1';
    }

    function cssEscape(value) {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }
        return String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
    }

    function medianViewport(points) {
        var values = points
            .map(function (point) { return point.vw || 0; })
            .filter(function (value) { return value > 0; })
            .sort(function (a, b) { return a - b; });

        if (!values.length) {
            return 0;
        }

        return values[Math.floor(values.length / 2)];
    }

    function resolvePoint(doc, point, docW, docH) {
        if (point.href && doc) {
            try {
                var href = String(point.href);
                var link = doc.querySelector('a[href="' + cssEscape(href) + '"]');
                if (!link && href.charAt(0) === '/') {
                    link = doc.querySelector('a[href="' + cssEscape(href.replace(/\/$/, '')) + '"]');
                }
                if (link) {
                    var rect = link.getBoundingClientRect();
                    var win = doc.defaultView;
                    var scrollX = win ? (win.pageXOffset || doc.documentElement.scrollLeft || 0) : 0;
                    var scrollY = win ? (win.pageYOffset || doc.documentElement.scrollTop || 0) : 0;
                    return {
                        x: rect.left + scrollX + (rect.width / 2),
                        y: rect.top + scrollY + (rect.height / 2),
                    };
                }
            } catch (error) {
                /* ignore */
            }
        }

        if (point.page_y > 0 && point.doc_h > 0 && point.doc_w > 0) {
            return {
                x: (point.page_x / point.doc_w) * docW,
                y: (point.page_y / point.doc_h) * docH,
            };
        }

        return {
            x: ((point.x_pct || 0) / 100) * docW,
            y: ((point.y_pct || 0) / 100) * docH,
        };
    }

    function drawPoints(ctx, doc, docW, docH, points) {
        ctx.clearRect(0, 0, docW, docH);

        if (!points.length) {
            return;
        }

        var clusters = {};

        points.forEach(function (point) {
            var pos = resolvePoint(doc, point, docW, docH);
            var key = Math.round(pos.x / 8) + ':' + Math.round(pos.y / 8);
            if (!clusters[key]) {
                clusters[key] = { x: pos.x, y: pos.y, weight: 0 };
            }
            clusters[key].weight += point.weight || 1;
        });

        var grouped = Object.keys(clusters).map(function (key) {
            return clusters[key];
        });

        var maxWeight = 1;
        grouped.forEach(function (point) {
            maxWeight = Math.max(maxWeight, point.weight || 1);
        });

        grouped.forEach(function (point) {
            var alpha = 0.28 + ((point.weight || 1) / maxWeight) * 0.55;
            var radius = 12 + ((point.weight || 1) / maxWeight) * 22;
            var radial = ctx.createRadialGradient(point.x, point.y, 0, point.x, point.y, radius);
            radial.addColorStop(0, 'rgba(219, 39, 119, ' + alpha + ')');
            radial.addColorStop(0.55, 'rgba(244, 63, 94, ' + (alpha * 0.55) + ')');
            radial.addColorStop(1, 'rgba(244, 63, 94, 0)');
            ctx.fillStyle = radial;
            ctx.beginPath();
            ctx.arc(point.x, point.y, radius, 0, Math.PI * 2);
            ctx.fill();
        });
    }

    function documentSize(doc) {
        var rootEl = doc.documentElement;
        var body = doc.body;
        var width = Math.max(
            rootEl.scrollWidth,
            body ? body.scrollWidth : 0,
            rootEl.clientWidth,
            1
        );
        var height = Math.max(
            rootEl.scrollHeight,
            body ? body.scrollHeight : 0,
            rootEl.clientHeight,
            1
        );

        return { width: width, height: height };
    }

    function initHeatmap(root) {
        var points = parseHeatmap(root);
        var pagePath = root.getAttribute('data-page-path') || '/';
        var empty = root.querySelector('.admin-analytics__heatmap-empty');
        var loading = root.querySelector('.admin-analytics__heatmap-loading');
        var scroll = root.querySelector('.admin-analytics__heatmap-scroll');
        var stage = root.querySelector('.admin-analytics__heatmap-stage');
        var iframe = root.querySelector('.admin-analytics__heatmap-frame');
        var canvas = root.querySelector('.admin-analytics__heatmap-overlay');
        var previewWidth = parseInt(root.getAttribute('data-preview-width') || '0', 10);

        if (!previewWidth) {
            previewWidth = medianViewport(points) || 1280;
        }

        previewWidth = Math.max(320, Math.min(1600, previewWidth));

        if (empty) {
            empty.hidden = points.length > 0;
            if (points.length > 0) {
                empty.setAttribute('aria-hidden', 'true');
            } else {
                empty.removeAttribute('aria-hidden');
            }
        }

        if (!iframe || !canvas || !stage) {
            return;
        }

        var ctx = canvas.getContext('2d');
        if (!ctx) {
            return;
        }

        var syncTimer = null;

        function hideLoading() {
            if (loading) {
                loading.hidden = true;
            }
        }

        function syncOverlay() {
            var doc;
            try {
                doc = iframe.contentDocument;
            } catch (error) {
                return;
            }

            if (!doc || !doc.body) {
                return;
            }

            var size = documentSize(doc);
            var docW = previewWidth;
            var docH = size.height;

            iframe.style.width = docW + 'px';
            iframe.style.height = docH + 'px';
            stage.style.width = docW + 'px';
            stage.style.height = docH + 'px';
            canvas.width = docW;
            canvas.height = docH;
            canvas.style.width = docW + 'px';
            canvas.style.height = docH + 'px';
            drawPoints(ctx, doc, docW, docH, points);
            hideLoading();
        }

        iframe.style.width = previewWidth + 'px';
        stage.style.width = previewWidth + 'px';

        function scheduleSync() {
            if (syncTimer) {
                window.clearTimeout(syncTimer);
            }
            syncTimer = window.setTimeout(syncOverlay, 120);
        }

        iframe.addEventListener('load', function () {
            scheduleSync();
            window.setTimeout(syncOverlay, 400);
            window.setTimeout(syncOverlay, 1200);
            window.setTimeout(syncOverlay, 2500);

            try {
                var doc = iframe.contentDocument;
                if (!doc) {
                    return;
                }

                if (typeof ResizeObserver !== 'undefined' && doc.body) {
                    var observer = new ResizeObserver(scheduleSync);
                    observer.observe(doc.body);
                    observer.observe(doc.documentElement);
                }

                Array.prototype.forEach.call(doc.images || [], function (image) {
                    if (!image.complete) {
                        image.addEventListener('load', scheduleSync, { once: true });
                    }
                });
            } catch (error) {
                /* ignore */
            }
        });

        iframe.src = pagePreviewUrl(pagePath);
    }

    function initGaRealtime(root) {
        var apiUrl = root.getAttribute('data-ga-api-url');
        var liveUrl = root.getAttribute('data-ga-live-url');
        var pollMode = root.getAttribute('data-ga-poll-mode') || 'full';
        if (!apiUrl && !liveUrl) {
            return;
        }

        var intervalMs = 30000;

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function renderBars(items) {
            if (!items || !items.length) {
                return '<p class="admin-empty">Немає даних</p>';
            }

            var max = 1;
            items.forEach(function (item) {
                max = Math.max(max, item.count || 0);
            });

            var html = '<ul class="admin-analytics__bars">';
            items.forEach(function (item) {
                var count = item.count || 0;
                var width = Math.max(6, Math.round((count / max) * 100));
                html += '<li>'
                    + '<span class="admin-analytics__bar-label">' + escapeHtml(item.label || '—') + '</span>'
                    + '<span class="admin-analytics__bar-track"><span class="admin-analytics__bar-fill" style="width:' + width + '%"></span></span>'
                    + '<span class="admin-analytics__bar-value">' + count + '</span>'
                    + '</li>';
            });
            html += '</ul>';

            return html;
        }

        function renderChart(chart) {
            var chartRoot = root.querySelector('[data-ga-chart]');
            if (!chartRoot) {
                return;
            }

            if (!chart || !chart.length) {
                chartRoot.innerHTML = '<p class="admin-empty">Немає даних за обраний період</p>';
                return;
            }

            var chartMax = 1;
            chart.forEach(function (point) {
                chartMax = Math.max(chartMax, point.sessions || 0, point.pageviews || 0);
            });

            var bars = '';
            chart.forEach(function (point) {
                var sessions = point.sessions || 0;
                var sessionH = Math.max(4, Math.round((sessions / chartMax) * 100));
                bars += '<div class="admin-analytics__chart-col" title="' + escapeHtml((point.date || '') + ': ' + sessions + ' активних') + '">'
                    + '<div class="admin-analytics__chart-bars">'
                    + '<span class="admin-analytics__chart-bar admin-analytics__chart-bar--sessions" style="height:' + sessionH + '%"></span>'
                    + '</div>'
                    + '<span class="admin-analytics__chart-label">' + escapeHtml(point.date || '') + '</span>'
                    + '</div>';
            });

            chartRoot.innerHTML = ''
                + '<div class="admin-analytics__chart">' + bars + '</div>'
                + '<div class="admin-analytics__legend">'
                + '<span><i class="admin-analytics__dot admin-analytics__dot--sessions"></i> Активні користувачі</span>'
                + '</div>';
        }

        function applyLive(live) {
            if (!live) {
                return;
            }
            var node = root.querySelector('[data-ga-live-active]');
            if (node && live.active_users !== undefined) {
                node.textContent = String(live.active_users);
            }
        }

        function applyReport(report) {
            if (!report || report.mode !== 'api' || report.error) {
                return;
            }

            var summary = report.summary || {};
            root.querySelectorAll('[data-ga-stat]').forEach(function (node) {
                var key = node.getAttribute('data-ga-stat');
                if (key && summary[key] !== undefined) {
                    node.textContent = String(summary[key]);
                }
            });

            renderChart(report.chart || []);
            applyLive(report.live || null);

            var breakdowns = {
                'Топ сторінки': report.top_pages || [],
                'Пристрої': report.devices || [],
                'Країни': report.sources || [],
            };

            Object.keys(breakdowns).forEach(function (title) {
                var panel = root.querySelector('[data-ga-breakdown="' + title + '"]');
                if (!panel) {
                    return;
                }
                var heading = panel.querySelector('h3');
                panel.querySelectorAll('.admin-analytics__bars, .admin-empty').forEach(function (node) {
                    node.remove();
                });
                if (heading) {
                    heading.insertAdjacentHTML('afterend', renderBars(breakdowns[title]));
                }
            });
        }

        function refresh() {
            var url = pollMode === 'full' ? apiUrl : liveUrl;
            if (!url) {
                return;
            }

            fetch(url, {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('bad response');
                    }
                    return response.json();
                })
                .then(function (payload) {
                    if (pollMode === 'full') {
                        applyReport(payload);
                        return;
                    }
                    applyLive(payload.live || null);
                })
                .catch(function () {
                    /* ignore transient errors */
                });
        }

        refresh();
        window.setInterval(refresh, intervalMs);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-analytics-heatmap]').forEach(initHeatmap);
        document.querySelectorAll('[data-ga-poll]').forEach(initGaRealtime);
    });
})();
