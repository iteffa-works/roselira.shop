(function () {
    'use strict';

    function parseHeatmap(root) {
        var raw = root.getAttribute('data-heatmap') || '[]';
        try {
            return JSON.parse(raw);
        } catch (error) {
            return [];
        }
    }

    function drawHeatmap(root) {
        var canvas = root.querySelector('.admin-analytics__heatmap-canvas');
        var empty = root.querySelector('.admin-analytics__heatmap-empty');
        if (!canvas) {
            return;
        }

        var points = parseHeatmap(root);
        var ctx = canvas.getContext('2d');
        if (!ctx) {
            return;
        }

        var width = canvas.width;
        var height = canvas.height;
        ctx.clearRect(0, 0, width, height);

        var gradient = ctx.createLinearGradient(0, 0, width, height);
        gradient.addColorStop(0, 'rgba(255, 244, 248, 0.95)');
        gradient.addColorStop(1, 'rgba(245, 247, 255, 0.95)');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, width, height);

        ctx.strokeStyle = 'rgba(0,0,0,0.06)';
        for (var gx = 0; gx <= 10; gx++) {
            var x = (width / 10) * gx;
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, height);
            ctx.stroke();
        }
        for (var gy = 0; gy <= 10; gy++) {
            var y = (height / 10) * gy;
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(width, y);
            ctx.stroke();
        }

        if (!points.length) {
            if (empty) {
                empty.hidden = false;
            }
            return;
        }

        if (empty) {
            empty.hidden = true;
        }

        var maxWeight = 1;
        points.forEach(function (point) {
            maxWeight = Math.max(maxWeight, point.weight || 1);
        });

        points.forEach(function (point) {
            var px = ((point.x_pct || 0) / 100) * width;
            var py = ((point.y_pct || 0) / 100) * height;
            var alpha = 0.25 + ((point.weight || 1) / maxWeight) * 0.55;
            var radius = 10 + ((point.weight || 1) / maxWeight) * 18;
            var radial = ctx.createRadialGradient(px, py, 0, px, py, radius);
            radial.addColorStop(0, 'rgba(219, 39, 119, ' + alpha + ')');
            radial.addColorStop(0.55, 'rgba(244, 63, 94, ' + (alpha * 0.55) + ')');
            radial.addColorStop(1, 'rgba(244, 63, 94, 0)');
            ctx.fillStyle = radial;
            ctx.beginPath();
            ctx.arc(px, py, radius, 0, Math.PI * 2);
            ctx.fill();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-analytics-heatmap]').forEach(drawHeatmap);
    });
})();
