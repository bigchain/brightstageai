/**
 * BrightStage Slideshow Preview
 * Full-screen slide navigation with keyboard support.
 */

const Slideshow = {
    overlay: null,
    slides: [],
    current: 0,
    _boundKeyHandler: null,

    /**
     * Open slideshow with array of slide image URLs.
     * @param {Array} slides - [{image_url, title, slide_order}]
     * @param {number} startIndex - Which slide to start on
     */
    open(slides, startIndex = 0) {
        this.slides = slides.filter(s => s.image_url);
        if (this.slides.length === 0) {
            alert('No rendered slides to preview. Click "Render Previews" first.');
            return;
        }
        this.current = startIndex;
        this.createOverlay();
        this.render();
        this._boundKeyHandler = (e) => this.handleKey(e);
        document.addEventListener('keydown', this._boundKeyHandler);
    },

    close() {
        if (this.overlay) {
            this.overlay.remove();
            this.overlay = null;
        }
        if (this._boundKeyHandler) {
            document.removeEventListener('keydown', this._boundKeyHandler);
            this._boundKeyHandler = null;
        }
    },

    next() {
        if (this.current < this.slides.length - 1) {
            this.current++;
            this.render();
        }
    },

    prev() {
        if (this.current > 0) {
            this.current--;
            this.render();
        }
    },

    handleKey(e) {
        if (e.key === 'ArrowRight' || e.key === ' ') { e.preventDefault(); Slideshow.next(); }
        if (e.key === 'ArrowLeft') { e.preventDefault(); Slideshow.prev(); }
        if (e.key === 'Escape') { Slideshow.close(); }
    },

    render() {
        const slide = this.slides[this.current];
        const img = this.overlay.querySelector('#ss-image');
        const counter = this.overlay.querySelector('#ss-counter');
        const title = this.overlay.querySelector('#ss-title');
        const prevBtn = this.overlay.querySelector('#ss-prev');
        const nextBtn = this.overlay.querySelector('#ss-next');

        img.src = slide.image_url;
        counter.textContent = `${this.current + 1} / ${this.slides.length}`;
        title.textContent = slide.title || `Slide ${slide.slide_order}`;

        prevBtn.style.opacity = this.current === 0 ? '0.3' : '1';
        nextBtn.style.opacity = this.current === this.slides.length - 1 ? '0.3' : '1';
    },

    createOverlay() {
        if (this.overlay) this.overlay.remove();

        const div = document.createElement('div');
        div.id = 'slideshow-overlay';
        div.innerHTML = `
            <div style="position:fixed;inset:0;background:rgba(0,0,0,0.95);z-index:9999;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                <!-- Top bar -->
                <div style="position:absolute;top:0;left:0;right:0;display:flex;align-items:center;justify-content:between;padding:16px 24px;z-index:10;">
                    <div style="flex:1;">
                        <span id="ss-title" style="color:#fff;font-size:14px;font-weight:600;opacity:0.8;"></span>
                    </div>
                    <div style="flex:1;text-align:center;">
                        <span id="ss-counter" style="color:#fff;font-size:13px;opacity:0.6;"></span>
                    </div>
                    <div style="flex:1;text-align:right;">
                        <button onclick="Slideshow.close()" style="color:#fff;background:rgba(255,255,255,0.1);border:none;padding:8px 16px;border-radius:8px;cursor:pointer;font-size:13px;">
                            &#10005; Close <span style="opacity:0.5;font-size:11px;">(Esc)</span>
                        </button>
                    </div>
                </div>

                <!-- Slide image -->
                <img id="ss-image" style="max-width:90vw;max-height:80vh;border-radius:8px;box-shadow:0 20px 60px rgba(0,0,0,0.5);object-fit:contain;" />

                <!-- Navigation arrows -->
                <button id="ss-prev" onclick="Slideshow.prev()" style="position:absolute;left:24px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.1);border:none;color:#fff;width:48px;height:48px;border-radius:50%;cursor:pointer;font-size:20px;display:flex;align-items:center;justify-content:center;transition:background 0.2s;">
                    &#9664;
                </button>
                <button id="ss-next" onclick="Slideshow.next()" style="position:absolute;right:24px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.1);border:none;color:#fff;width:48px;height:48px;border-radius:50%;cursor:pointer;font-size:20px;display:flex;align-items:center;justify-content:center;transition:background 0.2s;">
                    &#9654;
                </button>

                <!-- Bottom hint -->
                <div style="position:absolute;bottom:16px;text-align:center;">
                    <span style="color:#ffffff55;font-size:12px;">Arrow keys to navigate &middot; Esc to close</span>
                </div>
            </div>
        `;
        document.body.appendChild(div);
        this.overlay = div;
    },
};
