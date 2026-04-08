/**
 * BrightStage Slideshow Preview
 * Works with both rendered PNGs and live HTML slides.
 * If a slide has image_url, uses that. Otherwise renders HTML via html2canvas.
 */

const Slideshow = {
    overlay: null,
    slides: [],
    current: 0,
    _boundKeyHandler: null,

    /**
     * Open slideshow.
     * @param {Array} slides - [{image_url, html_content, title, slide_order}]
     */
    open(slides, startIndex = 0) {
        // Accept slides that have either image_url OR html_content
        this.slides = slides.filter(s => s.image_url || s.html_content);
        if (this.slides.length === 0) {
            toast('No slides to preview. Design your slides first.', 'warning');
            return;
        }
        this.current = startIndex;
        this.createOverlay();
        this.renderCurrent();
        this._boundKeyHandler = (e) => this.handleKey(e);
        document.addEventListener('keydown', this._boundKeyHandler);
    },

    close() {
        if (this.overlay) { this.overlay.remove(); this.overlay = null; }
        if (this._boundKeyHandler) {
            document.removeEventListener('keydown', this._boundKeyHandler);
            this._boundKeyHandler = null;
        }
    },

    next() { if (this.current < this.slides.length - 1) { this.current++; this.renderCurrent(); } },
    prev() { if (this.current > 0) { this.current--; this.renderCurrent(); } },

    handleKey(e) {
        if (e.key === 'ArrowRight' || e.key === ' ') { e.preventDefault(); Slideshow.next(); }
        if (e.key === 'ArrowLeft') { e.preventDefault(); Slideshow.prev(); }
        if (e.key === 'Escape') { Slideshow.close(); }
    },

    async renderCurrent() {
        const slide = this.slides[this.current];
        const counter = this.overlay.querySelector('#ss-counter');
        const title = this.overlay.querySelector('#ss-title');
        const imgEl = this.overlay.querySelector('#ss-image');
        const htmlEl = this.overlay.querySelector('#ss-html');
        const prevBtn = this.overlay.querySelector('#ss-prev');
        const nextBtn = this.overlay.querySelector('#ss-next');

        counter.textContent = `${this.current + 1} / ${this.slides.length}`;
        title.textContent = slide.title || `Slide ${slide.slide_order}`;
        prevBtn.style.opacity = this.current === 0 ? '0.3' : '1';
        nextBtn.style.opacity = this.current === this.slides.length - 1 ? '0.3' : '1';

        if (slide.image_url) {
            // Use rendered PNG
            imgEl.src = slide.image_url;
            imgEl.style.display = 'block';
            htmlEl.style.display = 'none';
        } else if (slide.html_content) {
            // Render live HTML in a scaled container
            imgEl.style.display = 'none';
            htmlEl.style.display = 'block';
            htmlEl.innerHTML = `<div style="width:1920px;height:1080px;transform:scale(${Math.min(window.innerWidth * 0.9 / 1920, window.innerHeight * 0.8 / 1080)});transform-origin:top left;">${slide.html_content}</div>`;
        }
    },

    createOverlay() {
        if (this.overlay) this.overlay.remove();

        const div = document.createElement('div');
        div.id = 'slideshow-overlay';
        div.innerHTML = `
            <div style="position:fixed;inset:0;background:rgba(0,0,0,0.95);z-index:9999;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                <!-- Top bar -->
                <div style="position:absolute;top:0;left:0;right:0;display:flex;align-items:center;padding:16px 24px;z-index:10;">
                    <div style="flex:1;"><span id="ss-title" style="color:#fff;font-size:14px;font-weight:600;opacity:0.8;"></span></div>
                    <div style="flex:1;text-align:center;"><span id="ss-counter" style="color:#fff;font-size:13px;opacity:0.6;"></span></div>
                    <div style="flex:1;text-align:right;">
                        <button onclick="Slideshow.close()" style="color:#fff;background:rgba(255,255,255,0.1);border:none;padding:8px 16px;border-radius:8px;cursor:pointer;font-size:13px;">
                            Close <span style="opacity:0.5;font-size:11px;">(Esc)</span>
                        </button>
                    </div>
                </div>

                <!-- Slide content -->
                <img id="ss-image" style="max-width:90vw;max-height:80vh;border-radius:8px;box-shadow:0 20px 60px rgba(0,0,0,0.5);object-fit:contain;display:none;" />
                <div id="ss-html" style="max-width:90vw;max-height:80vh;overflow:hidden;border-radius:8px;box-shadow:0 20px 60px rgba(0,0,0,0.5);display:none;"></div>

                <!-- Navigation -->
                <button id="ss-prev" onclick="Slideshow.prev()" style="position:absolute;left:24px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.1);border:none;color:#fff;width:48px;height:48px;border-radius:50%;cursor:pointer;font-size:20px;display:flex;align-items:center;justify-content:center;">&#9664;</button>
                <button id="ss-next" onclick="Slideshow.next()" style="position:absolute;right:24px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.1);border:none;color:#fff;width:48px;height:48px;border-radius:50%;cursor:pointer;font-size:20px;display:flex;align-items:center;justify-content:center;">&#9654;</button>

                <!-- Bottom -->
                <div style="position:absolute;bottom:16px;text-align:center;">
                    <span style="color:#ffffff55;font-size:12px;">Arrow keys to navigate &middot; Esc to close</span>
                </div>
            </div>
        `;
        document.body.appendChild(div);
        this.overlay = div;
    },
};
