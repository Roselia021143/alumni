(function () {
    const canvas = document.getElementById('tcParticleCanvas');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function initParticles() {
        if (!canvas || reducedMotion) {
            return;
        }

        const ctx = canvas.getContext('2d');
        const pointer = { x: 0, y: 0, active: false };
        let width = 0;
        let height = 0;
        let particles = [];

        function resize() {
            const ratio = window.devicePixelRatio || 1;
            width = window.innerWidth;
            height = window.innerHeight;
            canvas.width = width * ratio;
            canvas.height = height * ratio;
            canvas.style.width = width + 'px';
            canvas.style.height = height + 'px';
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);

            const count = Math.max(42, Math.min(110, Math.floor(width * height / 14000)));
            particles = Array.from({ length: count }, () => ({
                x: Math.random() * width,
                y: Math.random() * height,
                vx: (Math.random() - 0.5) * 0.42,
                vy: (Math.random() - 0.5) * 0.42,
                r: Math.random() * 1.8 + 0.9,
            }));
        }

        function movePointer(event) {
            pointer.x = event.clientX;
            pointer.y = event.clientY;
            pointer.active = true;
        }

        function draw() {
            ctx.clearRect(0, 0, width, height);

            particles.forEach((particle) => {
                if (pointer.active) {
                    const dx = pointer.x - particle.x;
                    const dy = pointer.y - particle.y;
                    const dist = Math.hypot(dx, dy);

                    if (dist < 150) {
                        particle.x -= dx * 0.002;
                        particle.y -= dy * 0.002;
                    }
                }

                particle.x += particle.vx;
                particle.y += particle.vy;

                if (particle.x < -10) particle.x = width + 10;
                if (particle.x > width + 10) particle.x = -10;
                if (particle.y < -10) particle.y = height + 10;
                if (particle.y > height + 10) particle.y = -10;
            });

            for (let i = 0; i < particles.length; i += 1) {
                for (let j = i + 1; j < particles.length; j += 1) {
                    const a = particles[i];
                    const b = particles[j];
                    const dist = Math.hypot(a.x - b.x, a.y - b.y);

                    if (dist < 118) {
                        const alpha = 1 - dist / 118;
                        ctx.strokeStyle = `rgba(66,245,155,${alpha * 0.18})`;
                        ctx.lineWidth = 1;
                        ctx.beginPath();
                        ctx.moveTo(a.x, a.y);
                        ctx.lineTo(b.x, b.y);
                        ctx.stroke();
                    }
                }
            }

            particles.forEach((particle) => {
                const glow = ctx.createRadialGradient(particle.x, particle.y, 0, particle.x, particle.y, particle.r * 5);
                glow.addColorStop(0, 'rgba(255,216,87,0.72)');
                glow.addColorStop(1, 'rgba(66,245,155,0)');
                ctx.fillStyle = glow;
                ctx.beginPath();
                ctx.arc(particle.x, particle.y, particle.r * 5, 0, Math.PI * 2);
                ctx.fill();

                ctx.fillStyle = 'rgba(248,250,252,0.8)';
                ctx.beginPath();
                ctx.arc(particle.x, particle.y, particle.r, 0, Math.PI * 2);
                ctx.fill();
            });

            requestAnimationFrame(draw);
        }

        window.addEventListener('resize', resize);
        window.addEventListener('pointermove', movePointer);
        window.addEventListener('pointerleave', () => {
            pointer.active = false;
        });
        resize();
        draw();
    }

    function drawTreeLines() {
        const stage = document.querySelector('.tc-tree-stage');
        const svg = document.querySelector('.tc-line-layer');

        if (!stage || !svg) {
            return;
        }

        const stageRect = stage.getBoundingClientRect();
        svg.innerHTML = [
            '<defs>',
            '<linearGradient id="tcLineGradient" x1="0%" y1="0%" x2="100%" y2="0%">',
            '<stop offset="0%" stop-color="#42f59b"/>',
            '<stop offset="100%" stop-color="#ffd857"/>',
            '</linearGradient>',
            '</defs>',
        ].join('');

        svg.setAttribute('viewBox', `0 0 ${stage.scrollWidth} ${stage.scrollHeight}`);
        svg.setAttribute('width', stage.scrollWidth);
        svg.setAttribute('height', stage.scrollHeight);

        const nodes = Array.from(stage.querySelectorAll('[data-node-id]'));
        const nodeMap = new Map(nodes.map((node) => [node.dataset.nodeId, node]));

        nodes.forEach((node, index) => {
            const parentId = node.dataset.parentNode;

            if (!parentId || !nodeMap.has(parentId)) {
                return;
            }

            const parent = nodeMap.get(parentId);
            const parentRect = parent.getBoundingClientRect();
            const childRect = node.getBoundingClientRect();

            const startX = parentRect.left - stageRect.left + stage.scrollLeft + parentRect.width / 2;
            const startY = parentRect.top - stageRect.top + stage.scrollTop + parentRect.height;
            const endX = childRect.left - stageRect.left + stage.scrollLeft + childRect.width / 2;
            const endY = childRect.top - stageRect.top + stage.scrollTop;
            const midY = startY + Math.max(34, (endY - startY) / 2);
            const pathData = `M ${startX} ${startY} C ${startX} ${midY}, ${endX} ${midY}, ${endX} ${endY}`;
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

            path.setAttribute('d', pathData);
            path.style.animationDelay = `${index * 0.08}s`;
            svg.appendChild(path);

            const length = path.getTotalLength();
            path.style.strokeDasharray = length;
            path.style.strokeDashoffset = length;
        });
    }

    function initReveal() {
        const items = document.querySelectorAll('.reveal-up');

        if (!items.length) {
            return;
        }

        if (!('IntersectionObserver' in window) || reducedMotion) {
            items.forEach((item) => item.classList.add('is-visible'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.18 });

        items.forEach((item) => observer.observe(item));
    }

    function initFlipOnTouch() {
        document.querySelectorAll('.tc-flip-card').forEach((card) => {
            card.addEventListener('click', () => {
                card.classList.toggle('is-touched');
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initParticles();
        initReveal();
        initFlipOnTouch();
        window.requestAnimationFrame(drawTreeLines);
    });

    window.addEventListener('resize', () => {
        window.clearTimeout(window.__tcLineTimer);
        window.__tcLineTimer = window.setTimeout(drawTreeLines, 160);
    });
})();
