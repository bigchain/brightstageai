/**
 * BrightStage Slide Renderer
 * Renders AI-generated HTML/CSS slides to PNG using html2canvas.
 * This is what gives us SurfSense-quality visuals without React/Remotion.
 *
 * Flow: AI generates HTML/CSS → inject into hidden div → html2canvas → PNG → upload to server
 */

const SlideRenderer = {
    // Hidden container for off-screen rendering
    container: null,

    /**
     * Initialize the renderer by creating the off-screen container.
     */
    init() {
        if (this.container) return;

        this.container = document.createElement('div');
        this.container.id = 'slide-render-container';
        this.container.style.cssText = `
            position: fixed;
            left: -9999px;
            top: -9999px;
            width: 1920px;
            height: 1080px;
            overflow: hidden;
            background: #000;
            z-index: -1;
        `;
        document.body.appendChild(this.container);
    },

    /**
     * Render a single slide HTML to a PNG data URL.
     * @param {string} html - The AI-generated HTML/CSS for the slide
     * @returns {Promise<string>} Base64 PNG data URL
     */
    async renderSlide(html) {
        this.init();

        // Inject the slide HTML
        this.container.innerHTML = html;

        // Wait for Google Fonts to load and images to render
        await this.waitForResources();

        // Capture with html2canvas
        const canvas = await html2canvas(this.container, {
            width: 1920,
            height: 1080,
            scale: 1,
            useCORS: true,
            allowTaint: false,
            backgroundColor: null,
            logging: false,
        });

        // Convert to PNG data URL
        const dataUrl = canvas.toDataURL('image/png');

        // Clean up
        this.container.innerHTML = '';

        return dataUrl;
    },

    /**
     * Wait for fonts and images to load.
     */
    async waitForResources() {
        // Wait for Google Fonts
        if (document.fonts && document.fonts.ready) {
            await document.fonts.ready;
        }

        // Wait for images
        const images = this.container.querySelectorAll('img');
        const promises = Array.from(images).map(img => {
            if (img.complete) return Promise.resolve();
            return new Promise((resolve) => {
                img.onload = resolve;
                img.onerror = resolve; // Don't block on failed images
            });
        });
        await Promise.all(promises);

        // Extra settling time for CSS transitions/animations
        await new Promise(r => setTimeout(r, 300));
    },

    /**
     * Render all slides and upload PNGs to server.
     * @param {number} presentationId
     * @param {Array} slides - Array of {id, html_content, slide_order}
     * @param {Function} onProgress - Callback (current, total, message)
     * @returns {Promise<{success: number, failed: number}>}
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
                // Render to PNG
                const dataUrl = await this.renderSlide(slide.html_content);

                // Upload to server
                onProgress(i + 1, total, `Uploading slide ${i + 1} of ${total}...`);
                const result = await api(`/api/slides/${slide.id}/upload-image`, {
                    image_data: dataUrl,
                });

                if (result.success) {
                    success++;
                    // Update the preview in the UI if it exists
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

    /**
     * Render a single slide for preview (no upload).
     * @param {string} html
     * @param {HTMLImageElement} imgElement - Target img to show preview
     */
    async previewSlide(html, imgElement) {
        if (!html) return;
        const dataUrl = await this.renderSlide(html);
        imgElement.src = dataUrl;
        imgElement.style.display = 'block';
    },
};
