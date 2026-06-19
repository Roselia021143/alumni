(function () {
    const viewport = document.getElementById('lineageViewport');
    const stage = document.getElementById('lineageStage');
    const canvas = document.getElementById('lineageCanvas');
    const output = document.getElementById('zoomLevel');

    if (!viewport || !stage || !canvas) return;

    const minScale = 0.3;
    const maxScale = 2;
    let scale = 1;
    let dragging = false;
    let startX = 0;
    let startY = 0;
    let startLeft = 0;
    let startTop = 0;
    const pointers = new Map();
    let pinchDistance = 0;
    let pinchScale = 1;

    function dimensions() {
        return { width: canvas.scrollWidth, height: canvas.scrollHeight };
    }

    function applyScale(nextScale, focusX, focusY) {
        const oldScale = scale;
        scale = Math.min(maxScale, Math.max(minScale, nextScale));
        const size = dimensions();
        canvas.style.transform = 'scale(' + scale + ')';
        stage.style.width = Math.max(viewport.clientWidth, size.width * scale) + 'px';
        stage.style.height = Math.max(viewport.clientHeight, size.height * scale) + 'px';

        if (typeof focusX === 'number' && typeof focusY === 'number') {
            const contentX = (viewport.scrollLeft + focusX) / oldScale;
            const contentY = (viewport.scrollTop + focusY) / oldScale;
            viewport.scrollLeft = contentX * scale - focusX;
            viewport.scrollTop = contentY * scale - focusY;
        }

        if (output) output.value = Math.round(scale * 100) + '%';
    }

    function fit() {
        const size = dimensions();
        const availableWidth = Math.max(100, viewport.clientWidth - 30);
        const availableHeight = Math.max(100, viewport.clientHeight - 30);
        applyScale(Math.min(1, availableWidth / size.width, availableHeight / size.height));
        requestAnimationFrame(function () {
            viewport.scrollLeft = Math.max(0, (size.width * scale - viewport.clientWidth) / 2);
            viewport.scrollTop = Math.max(0, (size.height * scale - viewport.clientHeight) / 2);
        });
    }

    document.querySelectorAll('[data-zoom]').forEach(function (button) {
        button.addEventListener('click', function () {
            const action = button.dataset.zoom;
            if (action === 'fit') return fit();
            const factor = action === 'in' ? 1.2 : 1 / 1.2;
            applyScale(scale * factor, viewport.clientWidth / 2, viewport.clientHeight / 2);
        });
    });

    viewport.addEventListener('wheel', function (event) {
        event.preventDefault();
        const rect = viewport.getBoundingClientRect();
        const factor = Math.exp(-event.deltaY * 0.0015);
        applyScale(scale * factor, event.clientX - rect.left, event.clientY - rect.top);
    }, { passive: false });

    viewport.addEventListener('pointerdown', function (event) {
        pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });
        viewport.setPointerCapture(event.pointerId);

        if (pointers.size === 1) {
            dragging = true;
            startX = event.clientX;
            startY = event.clientY;
            startLeft = viewport.scrollLeft;
            startTop = viewport.scrollTop;
            viewport.classList.add('is-dragging');
        } else if (pointers.size === 2) {
            const points = Array.from(pointers.values());
            pinchDistance = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);
            pinchScale = scale;
            dragging = false;
        }
    });

    viewport.addEventListener('pointermove', function (event) {
        if (!pointers.has(event.pointerId)) return;
        pointers.set(event.pointerId, { x: event.clientX, y: event.clientY });

        if (pointers.size === 2) {
            const points = Array.from(pointers.values());
            const distance = Math.hypot(points[0].x - points[1].x, points[0].y - points[1].y);
            const rect = viewport.getBoundingClientRect();
            const middleX = (points[0].x + points[1].x) / 2 - rect.left;
            const middleY = (points[0].y + points[1].y) / 2 - rect.top;
            if (pinchDistance > 0) applyScale(pinchScale * distance / pinchDistance, middleX, middleY);
        } else if (dragging) {
            viewport.scrollLeft = startLeft - (event.clientX - startX);
            viewport.scrollTop = startTop - (event.clientY - startY);
        }
    });

    function release(event) {
        pointers.delete(event.pointerId);
        if (pointers.size === 0) {
            dragging = false;
            viewport.classList.remove('is-dragging');
        }
    }

    viewport.addEventListener('pointerup', release);
    viewport.addEventListener('pointercancel', release);
    viewport.addEventListener('dblclick', fit);
    window.addEventListener('resize', fit);
    window.addEventListener('load', fit);
    requestAnimationFrame(fit);
}());
