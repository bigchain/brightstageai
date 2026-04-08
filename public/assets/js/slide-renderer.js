/**
 * BrightStage Slide Renderer
 * Renders AI-generated HTML/CSS slides to PNG using html2canvas.
 *
 * Known issues addressed:
 * - Google Fonts @import inside slide HTML won't load off-screen → we preload them
 * - html2canvas needs the element to be in the DOM and styled
 * - Large canvas (1920x1080) needs proper memory handling
 */

const SlideRenderer = {
    container: null,

    init() {
        if (this.container) return;

        this.container = document.createElement('div');
        this.container.id = 'slide-render-container';
        // Position off-screen but still rendered (not display:none)
        this.container.style.cssText = `
            position: absolute;
            left: -3000px;
            top: 0;
            width: 1920px;
            height: 1080px;
            overflow: hidden;
            background: #000;
            z-index: -1;
        `;
        document.body.appendChild(this.container);
    },

    /**
     * Extract @import font URLs from HTML and preload them.
     */
    async preloadFonts(html) {
        const imports = html.match(/@import\s+url\(['"]?([^'")\s]+)['"]?\)/gi) || [];
        const fontUrls = imports.map(imp => {
            const match = imp.match(/url\(['"]?([^'")\s]+)['"]?\)/);
            return match ? match[1] : null;
        }).filter(Boolean);

        // Load each font stylesheet
        const promises = fontUrls.map(url => {
            return new Promise(resolve => {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = url;
                link.onload = resolve;
                link.onerror = resolve; // Don't block on failure
                document.head.appendChild(link);
            });
        });

        if (promises.length > 0) {
            await Promise.all(promises);
            // Wait for font rendering
            if (document.fonts && document.fonts.ready) {
                await document.fonts.ready;
            }
        }
    },

    /**
     * Render a single slide HTML to a PNG data URL.
     */
    async renderSlide(html) {
        this.init();

        // Preload any Google Fonts in the HTML
        await this.preloadFonts(html);

        // Inject the slide HTML
        this.container.innerHTML = html;

        // Wait for all resources
        await this.waitForResources();

        // Capture with html2canvas
        const canvas = await html2canvas(this.container, {
            width: 1920,
            height: 1080,
            scale: 1,
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#000000',
            logging: false,
        });

        const dataUrl = canvas.toDataURL('image/png');
        this.container.innerHTML = '';

        return dataUrl;
    },

    async waitForResources() {
        // Wait for fonts
        if (document.fonts && document.fonts.ready) {
            await document.fonts.ready;
        }

        // Wait for images
        const images = this.container.querySelectorAll('img');
        const promises = Array.from(images).map(img => {
            if (img.complete) return Promise.resolve();
            return new Promise(resolve => {
                img.onload = resolve;
                img.onerror = resolve;
            });
        });
        await Promise.all(promises);

        // Settling time for CSS
        await new Promise(r => setTimeout(r, 500));
    },

    /**
     * Render all slides and upload PNGs to server.
     */
    async renderAndUploadAll(presentationId, slides, onProgress) {
        this.init();

        let success = 0;
        let failed = 0;
        const total = slides.length;

        for (let i = 0; i < slides.length; i++) {
            const slide = slides[i];
            onProgress(i + 1, total, `Rendering slide ${i + 1} of ${total}...`);

            if (!slide.html_content) {
                failed++;
                continue;
            }

            try {
                const dataUrl = await this.renderSlide(slide.html_content);

                onProgress(i + 1, total, `Uploading slide ${i + 1} of ${total}...`);
                const result = await api(`/api/slides/${slide.id}/upload-image`, {
                    image_data: dataUrl,
                });

                if (result.success) {
                    success++;
                    // Update preview if visible
                    const preview = document.querySelector(`#slide-preview-${slide.id}`);
                    if (preview) {
                        preview.src = dataUrl;
                        preview.style.display = 'block';
                    }
                } else {
                    failed++;
                }
            } catch (err) {
                console.error(`Failed to render slide ${slide.id}:`, err);
                failed++;
            }
        }

        onProgress(total, total, `Done! ${success} slides rendered.`);
        return { success, failed };
    },
};
