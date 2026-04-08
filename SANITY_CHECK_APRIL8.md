# BrightStage Video — Sanity Check & Fixes

**Date:** 2026-04-08  
**Scope:** Full code, UI/UX, and security review  
**Result:** 9 fixes across 7 files (+123 lines, -33 lines)

---

## Overall Verdict

Solid MVP. Clean custom PHP MVC architecture (~3,900 lines, 38 files). Strong security fundamentals. No critical vulnerabilities found. Issues were mostly hardening, UX polish, and missing guardrails on expensive operations.

---

## Fixes Applied

### 1. Rate Limiting Enabled on AI Endpoints

**File:** `public/index.php`  
**Problem:** `check_rate_limit()` existed in `src/middleware/rate_limit.php` but was never called. All AI generation endpoints were unprotected — someone could burn through OpenRouter credits rapidly.  
**Fix:** Added `require_once` for rate_limit.php and rate limit checks on 6 endpoints:

| Endpoint | Limit |
|----------|-------|
| `/api/generate/image` | 20/min |
| `/api/generate/slides/{id}` | 10/min |
| `/api/slides/{id}/regenerate-design` | 20/min |
| `/api/generate/audio/{id}` | 5/min |
| `/api/generate/video/{id}` | 3/min |
| `/api/generate/outline-preview` | 5/min |

**Note:** Rate limiter counts `credit_transactions` (successful ops only). Failed requests don't increment the counter. A proper fix would need a separate `api_requests` table — left as future improvement.

---

### 2. PDF Download Fixed

**File:** `public/index.php`  
**Problem:** `/api/presentations/{id}/download-pdf` returned raw HTML with no `Content-Disposition` header. Users clicking "PDF" got a browser tab of HTML, not a download.  
**Fix:**
- Added `Content-Disposition: inline` header with sanitized filename
- Added `@media print` CSS rules
- Added `window.print()` on load — auto-opens browser's "Save as PDF" dialog
- Added `.no-print` utility class for print-hidden elements

---

### 3. HTML Sanitization on Slide Updates

**File:** `src/controllers/ApiSlideController.php`  
**Problem:** `SlideGenerationService` sanitized AI-generated HTML (stripping scripts, event handlers, iframes, etc.), but `ApiSlideController::update()` saved `html_content` raw. User edits bypassed sanitization.  
**Fix:** Added `sanitize_html()` method to ApiSlideController that strips:
- `<script>` tags
- `on*` event handlers (onclick, onerror, etc.)
- `<iframe>`, `<object>`, `<embed>`, `<applet>` tags
- `<form>`, `<input>`, `<button>`, `<select>`, `<textarea>` tags
- `javascript:` URLs

Matches the sanitizer in SlideGenerationService exactly.

---

### 4. Custom Rename Modal (Replaces browser `prompt()`)

**Files:** `src/templates/layouts/base.php`, `src/templates/pages/dashboard.php`  
**Problem:** Dashboard rename used browser `prompt()` which looks inconsistent with the custom `confirmAction()` modal used elsewhere.  
**Fix:**
- Added `promptInput(label, defaultValue, onSubmit)` global function in base.php
- Styled consistently with existing `confirmAction()` modal
- Supports Enter key to submit, Escape/backdrop to cancel
- Sets default value via DOM property (`input.value = ...`) not innerHTML — prevents XSS
- Updated `renameProject()` in dashboard.php to use it

---

### 5. Lazy Loading on Slide Preview Images

**File:** `src/templates/pages/presentation.php`  
**Problem:** All slide preview images loaded eagerly — slow on presentations with many slides.  
**Fix:** Added `loading="lazy"` attribute to slide preview `<img>` tags.

---

### 6. Debounced Slide Saves

**File:** `src/templates/pages/presentation.php`  
**Problem:** Every keystroke in slide fields could trigger save if user clicked quickly. No protection against concurrent saves on the same slide (race conditions).  
**Fix:**
- `markDirty()` now auto-saves after 1.5s debounce (resets on each edit)
- `saveSlide()` has a `savingSlides` Set guard — prevents concurrent saves for the same slide
- Manual "Save Changes" click clears any pending debounce timer
- Added `saveTimers` and `savingSlides` tracking objects

---

### 7. Font Loading Optimized

**File:** `src/templates/layouts/base.php`  
**Problem:** 5 Google Font families loaded synchronously on every page (~400KB). Most pages only need Inter (the UI font). Template fonts (Poppins, Playfair, Raleway, Montserrat) are only needed for slide previews.  
**Fix:**
- Inter loads immediately (critical UI font), reduced to weights 400/600/700
- Template fonts deferred via `media="print" onload="this.media='all'"` pattern
- Reduced Poppins to 600/700, Montserrat to 700 only
- Page renders faster; template fonts load after interactive

---

### 8. Slide Renderer Retry Logic

**File:** `public/assets/js/slide-renderer.js`  
**Problem:** Single render failure = permanent gap. No retry. Also, hardcoded 500ms CSS wait was too short for heavy slides and too long for simple ones.  
**Fix:**
- Each slide gets 2 render attempts (500ms wait between retries)
- CSS settling time now adaptive: 800ms if slide has images or @import fonts, 300ms otherwise
- Failure logged with attempt count for debugging

---

### 9. Video Worker Skipped Slide Warnings

**Files:** `workers/video_worker.php`, `src/templates/pages/presentation.php`  
**Problem:** Video worker silently skipped slides without rendered images. Users got incomplete videos with no explanation.  
**Fix:**
- Worker tracks `$skipped_slides` array with slide numbers
- On completion, `progress_message` includes "slides X, Y skipped (no rendered images)" if any were skipped
- Presentation page shows amber warning banner when video has skipped slides
- Banner text: "Render all slides before generating video for a complete result."

---

## Self-Review: Bugs Caught After Initial Implementation

### Bug A: Incomplete HTML sanitizer
**Found:** `sanitize_html()` in ApiSlideController initially only stripped `<form>` but missed `<input>`, `<button>`, `<select>`, `<textarea>`. SlideGenerationService strips all of them.  
**Fixed:** Added missing tags to match the service's sanitizer.

### Bug B: XSS in promptInput
**Found:** Default value was injected via innerHTML with only `"` escaping. A title containing `"><img src=x onerror=alert(1)>` could break out of the attribute.  
**Fixed:** Changed to set `input.value` via DOM property (safe — no HTML parsing).

### Cleanup: Dead variable
**Found:** `rendered` variable in slide-renderer retry loop was assigned but never read.  
**Fixed:** Removed.

---

## Security Assessment (No Issues Found)

These were all verified as solid:
- All SQL uses PDO prepared statements (no injection vectors)
- CSRF tokens on every state-changing endpoint, verified with `hash_equals()`
- Bcrypt password hashing + session fixation prevention
- Ownership checks on every resource access (`WHERE user_id = ?`)
- `SELECT FOR UPDATE` prevents credit race conditions
- Prompt injection mitigated (user input treated as data, not instructions)
- `escapeshellarg()` on all FFmpeg commands
- Security headers set globally (X-Frame-Options, HSTS, X-Content-Type-Options, etc.)
- File upload validation (MIME type, extension whitelist, size limits)

---

## Remaining Items (Not Fixed — Future Work)

| Item | Priority | Notes |
|------|----------|-------|
| ~1100 lines inline JS in presentation.php | Medium | Works but hard to maintain. Could extract to `presentation-editor.js` |
| Toast notifications not accessible | Low | No ARIA live region — screen readers can't hear them |
| Rate limiter only counts successful ops | Low | Failed API calls don't increment counter. Needs separate table |
| No admin panel | Medium | Admin role exists in DB but no UI built |
| Stripe billing not integrated | High | Pricing tiers defined, no webhook handling yet |
| No email notifications | Medium | No email service configured |
| No 2FA | Low | Single-factor auth only |

---

## Files Changed Summary

```
public/assets/js/slide-renderer.js     | 55 +++++++++++++++++--------------
public/index.php                       | 15 ++++++++--
src/controllers/ApiSlideController.php | 16 +++++++++-
src/templates/layouts/base.php         | 33 +++++++++++++++++++-
src/templates/pages/dashboard.php      |  6 ++--
src/templates/pages/presentation.php   | 20 +++++++++++++
workers/video_worker.php               | 11 +++++--
7 files changed, 123 insertions(+), 33 deletions(-)
```
