document.addEventListener('alpine:init', () => {
    const MIN_W = 280;
    const MIN_H = 220;
    const DEF_W = 500;
    const DEF_H = 400;
    const GAP = 4;
    const PAD = 16;

    Alpine.data('messengerFloatPanel', () => ({
        open: false,
        w: DEF_W,
        h: DEF_H,
        left: 0,
        top: 0,

        init() {
            window.addEventListener('resize', () => {
                if (this.open) {
                    this.clampFixed();
                }
            });
        },

        mainBounds() {
            return this.$root.getBoundingClientRect();
        },

        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => this.resetPosition());
            }
        },

        resetPosition() {
            this.w = DEF_W;
            this.h = DEF_H;
            const m = this.mainBounds();
            const rail = document.getElementById('app-messenger-right-rail');
            const railRect = rail
                ? rail.getBoundingClientRect()
                : { left: window.innerWidth };
            const anchorRight = railRect.left - GAP;
            this.left = anchorRight - this.w;
            this.top = m.top + PAD;
            this.clampFixed();
        },

        clampFixed() {
            const m = this.mainBounds();
            this.w = Math.max(MIN_W, Math.min(this.w, m.width - 2 * PAD));
            this.h = Math.max(MIN_H, Math.min(this.h, m.height - 2 * PAD));
            this.left = Math.min(Math.max(this.left, m.left + PAD), m.right - PAD - this.w);
            this.top = Math.min(Math.max(this.top, m.top + PAD), m.bottom - PAD - this.h);
        },

        startDrag(e) {
            if (e.button !== 0) {
                return;
            }
            if (e.target.closest('button,a,[data-no-drag]')) {
                return;
            }
            const startX = e.clientX;
            const startY = e.clientY;
            const origL = this.left;
            const origT = this.top;
            const onMove = (ev) => {
                this.left = origL + (ev.clientX - startX);
                this.top = origT + (ev.clientY - startY);
                this.clampFixed();
            };
            const onUp = () => {
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup', onUp);
            };
            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup', onUp);
        },

        startResize(edge, e) {
            if (e.button !== 0) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            const startX = e.clientX;
            const startY = e.clientY;
            const sw = this.w;
            const sh = this.h;
            const sl = this.left;
            const st = this.top;
            const onMove = (ev) => {
                const dx = ev.clientX - startX;
                const dy = ev.clientY - startY;
                let w = sw;
                let h = sh;
                let left = sl;
                let top = st;
                if (edge.includes('e')) {
                    w = sw + dx;
                }
                if (edge.includes('s')) {
                    h = sh + dy;
                }
                if (edge.includes('w')) {
                    left = sl + dx;
                    w = sw - dx;
                }
                if (edge.includes('n')) {
                    top = st + dy;
                    h = sh - dy;
                }
                this.w = w;
                this.h = h;
                this.left = left;
                this.top = top;
                this.clampFixed();
            };
            const onUp = () => {
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup', onUp);
            };
            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup', onUp);
        },
    }));
});
