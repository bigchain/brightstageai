# BrightStage Video - Complete Spec

**Date:** 2026-04-07
**Status:** DRAFT - Awaiting confirmation before build

---

## What Is BrightStage Video?

An AI-powered platform that turns a topic into a complete video presentation AND a full marketing media kit. Users provide a topic, the AI generates slides with narration, produces a downloadable video, and then generates everything needed to promote it — social posts, emails, articles, images, press releases.

**One-liner:** Topic in → Video + Media Kit out.

---

## Core User Journey (5 Steps)

### Step 1: Content Input
- User enters: topic, target audience, duration (5/10/15/30 min), tone
- AI generates an outline with slide titles + talking points
- User can edit/reorder/add/remove slides before proceeding

### Step 2: Slide Design
- User picks a template/theme (colors, fonts, layout style)
- AI generates beautiful HTML/CSS slides (SurfSense-quality rendering)
- User can edit individual slides (text, layout, background)
- Slides rendered in browser via `html2canvas` → PNG images

### Step 3: Voice & Video
- User selects a voice (TTS provider options)
- AI generates narration script per slide (from talking points)
- User can edit scripts before generation
- System generates audio per slide, then assembles full video server-side (FFmpeg)

### Step 4: Export & Share
- Preview the final video in-browser
- Download options: MP4 video, Visual PPTX (beautiful), Editable PPTX (editable text), PDF
- Share link (optional)

### Step 5: Media Kit
- User selects a completed presentation
- Clicks "Generate Media Kit"
- AI generates all written assets (social posts, emails, articles, press release, landing page copy)
- Image assets generated (platform-specific sizes for every social network)
- User previews, edits individual assets, downloads individually or full kit as ZIP

---

## Confirmed Tech Stack

### What we're using:
| Layer | Technology | Details |
|-------|-----------|---------|
| **Frontend** | HTML/CSS/vanilla JS | Simple, fast, no build tooling |
| **Backend** | PHP 8+ | Shared hosting at ai.brightstageai.com |
| **Database** | MySQL via phpMyAdmin | cPanel managed |
| **AI/LLM** | OpenRouter API | Access to multiple models (GPT-4, Claude, etc.) |
| **Slide Rendering** | AI HTML/CSS + html2canvas | SurfSense-quality slides, browser-rendered to PNG |
| **TTS** | Via OpenRouter or dedicated TTS API | Voice narration |
| **Video Assembly** | Server-side FFmpeg | Batch segments → concat |
| **PPTX Export** | PHPPresentation (phpoffice) | Editable PowerPoint files |
| **Payments** | Stripe | Subscriptions + credit top-ups |
| **File Storage** | Server filesystem | Under hosting account |
| **Hosting** | cPanel shared hosting | brightstageai.com |
| **Git** | github.com/bigchain/brightstageai | Private repo |

### What we're NOT using:
- ~~Supabase~~ → MySQL + phpMyAdmin
- ~~React/Vite/TypeScript~~ → Keep frontend simple (vanilla JS)
- ~~WordPress plugin~~ → Standalone PHP app
- ~~Client-side FFmpeg~~ → Server-side only (old version crashed 40% of mobile users)
- ~~Edge Functions~~ → Plain PHP endpoints
- ~~Remotion~~ → html2canvas + FFmpeg (same quality, 10x simpler)

---

## Slide Rendering — Hybrid Approach (SurfSense-Quality)

### How SurfSense does it:
SurfSense has the AI generate React/JSX code per slide, renders it via Remotion (a React video framework), then exports via `dom-to-pptx`. This requires React, Remotion, headless browser, Celery workers, and LangGraph. Way too complex.

### Our approach (same visual quality, 10x simpler):

**Step 1:** AI generates **plain HTML/CSS per slide** via OpenRouter:
```
Prompt: "Generate an HTML slide (1920x1080) about 'Revenue Growth Q4'.
Use modern CSS: gradients, Google Fonts, flexbox layout.
Return only the HTML with inline styles. No JavaScript."
```

**Step 2:** Browser renders the HTML in a hidden div, then `html2canvas` captures it as a PNG:
```javascript
// In browser
const slideDiv = document.getElementById('slide-render');
slideDiv.innerHTML = aiGeneratedHTML;
const canvas = await html2canvas(slideDiv, { width: 1920, height: 1080 });
const png = canvas.toDataURL('image/png');
// Send PNG to server via AJAX
```

**Step 3:** PNGs sent to server for video assembly + PPTX packaging

### Why this works:
- HTML/CSS is the most powerful design tool (every beautiful website proves this)
- AI is excellent at generating HTML/CSS (it's trained on billions of web pages)
- `html2canvas` is a free, lightweight JS library (~40KB)
- No headless browser, no React, no build tools needed
- Gradients, Google Fonts, shadows, flexbox — all supported
- Result: designer-quality slides from plain PHP + vanilla JS

### Slide HTML storage:
Each slide's HTML/CSS is stored in the `slides.html_content` field, so it can be re-rendered or edited later.

---

## Video Generation Pipeline (Server-Side)

This was the #1 problem in the old version (crashed 40% of mobile users because FFmpeg ran in the browser). Now it's 100% server-side using a batch approach:

### The Pipeline:

```
┌─────────────────────────────────────────────────────┐
│ 1. USER CLICKS "GENERATE VIDEO"                     │
│    → PHP creates job in videos table (status:queued) │
├─────────────────────────────────────────────────────┤
│ 2. CRON WORKER PICKS UP JOB (runs every 30s)       │
│    → Updates status to 'processing'                  │
├─────────────────────────────────────────────────────┤
│ 3. PER-SLIDE PROCESSING (batch loop)                │
│    For each slide:                                   │
│    a) Get slide PNG from storage                     │
│    b) Get slide audio MP3 from storage               │
│    c) FFmpeg: image + audio → segment MP4            │
│       ffmpeg -loop 1 -i slide_001.png               │
│              -i audio_001.mp3                        │
│              -c:v libx264 -tune stillimage           │
│              -c:a aac -shortest                      │
│              segment_001.mp4                         │
├─────────────────────────────────────────────────────┤
│ 4. CONCATENATION                                     │
│    Write segments.txt listing all segment files      │
│    ffmpeg -f concat -safe 0 -i segments.txt         │
│           -c copy final_output.mp4                   │
├─────────────────────────────────────────────────────┤
│ 5. FINALIZE                                          │
│    → Move final.mp4 to storage                       │
│    → Update videos table (status:complete, file_url) │
│    → Clean up temp segment files                     │
├─────────────────────────────────────────────────────┤
│ 6. FRONTEND POLLS for status every 3-5 seconds      │
│    → When complete, download button appears          │
└─────────────────────────────────────────────────────┘
```

### Background processing:
- **MVP:** PHP CLI script triggered by cron every 30 seconds
- **Later:** Supervisor + PHP worker process for better reliability

### Progress tracking:
The `videos` table tracks progress. Frontend polls `GET /api/videos/{id}/status` and shows:
- "Queued..." → "Processing slide 3/10..." → "Assembling final video..." → "Complete!"

---

## PPTX Export — Dual Output

Users get TWO PowerPoint options:

| Export Type | Quality | Editable? | How |
|-------------|---------|-----------|-----|
| **Visual PPTX** | Beautiful (AI-designed) | No (images) | Slide PNGs packaged as full-page images in PPTX |
| **Editable PPTX** | Clean, professional | Yes (real text boxes) | PHPPresentation builds structured slides |

### Visual PPTX (image-based):
- Each slide PNG → inserted as full-page image in PPTX
- Looks exactly like the video slides
- Not editable but perfect for viewing/presenting

### Editable PPTX (PHPPresentation):
- `phpoffice/phppresentation` PHP library builds real PowerPoint
- Text boxes, bullet points, fonts, colors, backgrounds
- Fully editable in PowerPoint/Google Slides
- Looks clean and professional (not as fancy as the HTML-rendered version)

### Evolution path:
- **MVP:** PHPPresentation with coded layouts → clean, professional (7/10 quality)
- **Post-launch:** Pre-designed PPTX templates (by a designer) + PHPPresentation fills content → beautiful, polished (9/10 quality)

### Template strategy (post-launch):
5 starter templates, each with 4-6 master layouts:
1. **Corporate** — navy/white, clean, minimal
2. **Creative** — bold colors, modern layouts
3. **Minimal** — lots of whitespace, elegant
4. **Dark** — dark backgrounds, light text, techy feel
5. **Vibrant** — gradients, energetic, startup-y

Master layouts per template: Title slide, Section divider, Bullets + image, Full image, Two-column, Quote/callout

---

## Media Kit Generator

After creating a presentation, users can generate a complete promotional media kit — everything needed to market and promote their webinar/presentation.

**This is a market gap.** No single tool today generates a complete media kit from one input. Canva does graphics, Mailchimp does emails, Buffer does social posts — but nobody does it all.

### Written Assets (AI-generated via OpenRouter):

| Asset | Details | Format |
|-------|---------|--------|
| **Twitter/X posts** | 3-5 variants, 280 chars each, with hashtags | Plain text |
| **LinkedIn post** | 1 long-form post (3000 chars max), engagement-optimized | Plain text |
| **LinkedIn article** | 1000-2000 word thought-leadership piece | HTML |
| **Facebook post** | 1-2 variants, with engagement hooks | Plain text |
| **Instagram captions** | 1-2 variants with hashtag sets (3-5 per post) | Plain text |
| **Email sequence (pre-event)** | 5 emails: invite, social proof, new angle, day-before, starting-now | HTML |
| **Email sequence (post-event)** | 3 emails: replay + thank you, value extension, final follow-up | HTML |
| **Blog article** | 800-1500 words, SEO-friendly, WordPress-ready | HTML |
| **Press release** | Standard format: headline, lead, quote, details, boilerplate | HTML |
| **Landing page copy** | Above/below fold: headline, bullets, speaker bio, FAQ, CTA | HTML |

### Image Assets:

| Asset | Dimensions | Platform |
|-------|-----------|----------|
| Twitter/X image | 1200 x 675 px | Twitter |
| LinkedIn image | 1080 x 1080 px | LinkedIn |
| Facebook image | 1200 x 630 px | Facebook |
| Instagram feed image | 1080 x 1350 px | Instagram |
| Instagram/FB Story | 1080 x 1920 px | Instagram, Facebook |
| YouTube thumbnail | 1280 x 720 px | YouTube |
| Email header | 1200 x 200 px | Email |
| Speaker card | 1080 x 1080 px | Multi-platform |
| Quote graphics (x3-5) | 1080 x 1080 px | Multi-platform |
| Blog featured image | 1200 x 630 px | Blog, OG tags |

### Image generation approach:
- **MVP:** AI generates HTML/CSS templates per image size → `html2canvas` renders to PNG (same approach as slides)
- **Later:** AI image generation API (DALL-E, Flux) for more creative/photographic images

### How Media Kit generation works:
1. User selects a completed presentation → clicks "Generate Media Kit"
2. PHP sends presentation data (title, topic, outline, key points, speaker info) to OpenRouter
3. Single AI call with structured prompt generates ALL written assets at once (efficient, ~1 API call)
4. Image templates rendered in browser via html2canvas
5. All assets stored in `media_kit_assets` table
6. User sees dashboard with all assets — can preview, edit text inline, regenerate individual items
7. Download individual assets or full kit as ZIP

---

## Credit System & Monetization

### Subscription Tiers (Stripe Monthly):

| Plan | Monthly Price | Credits Included | Extra Credit Price |
|------|--------------|------------------|--------------------|
| **Free** | $0 | 100 (one-time signup bonus) | Cannot purchase |
| **Starter** | $19/mo | 500 | $0.05/credit |
| **Pro** | $49/mo | 1,500 | $0.04/credit |
| **Business** | $99/mo | 4,000 | $0.03/credit |

### Top-Up Credit Packages:

| Package | Price | Credits | Best For |
|---------|-------|---------|----------|
| Small | $5 | 100 | Quick top-up |
| Medium | $15 | 350 | Extra project |
| Large | $30 | 800 | Heavy month |

### Credit Costs Per Action:

| Action | Credits | Notes |
|--------|---------|-------|
| Generate outline | 5 | Per presentation |
| Generate slide content (per slide) | 2 | Text content |
| Generate AI image (per slide) | 3 | HTML/CSS rendered image |
| Generate TTS audio (per slide) | 2 | Per slide narration |
| Assemble video | 10 | Full FFmpeg pipeline |
| Export Visual PPTX | 3 | Image-based |
| Export Editable PPTX | 5 | PHPPresentation built |
| Export PDF | 3 | From slide images |
| **Generate full media kit** | **25** | All written + image assets |
| Generate social posts only | 8 | Twitter, LinkedIn, FB, IG |
| Generate email sequence only | 10 | 8 emails total |
| Generate blog article only | 8 | 800-1500 words |
| Generate press release only | 5 | Standard format |
| Generate image set only | 10 | All platform sizes |

### Credit Math (sanity check):
A full 10-slide presentation + video + media kit:
- Outline: 5
- Slides: 10 x 2 = 20
- Images: 10 x 3 = 30
- Audio: 10 x 2 = 20
- Video: 10
- Media kit: 25
- **Total: ~110 credits**

So: Free (100) = almost 1 full run to try | Starter (500) = ~4/month | Pro (1500) = ~13/month | Business (4000) = ~36/month

### Admin Credit Tracking:
- **Dashboard:** total credits sold, consumed, remaining across all users
- **Per-user view:** credit history, usage timeline, top actions
- **Alerts:** high-consumption spikes, potential abuse patterns
- **Reports:** CSV export of all transactions, monthly revenue summary
- **Controls:** manually adjust user credits, disable accounts, set per-user limits

---

## Database Schema (MySQL)

### Users
```
users
├── id (INT, AUTO_INCREMENT, PK)
├── email (VARCHAR 255, UNIQUE)
├── password_hash (VARCHAR 255)
├── name (VARCHAR 255)
├── credits_balance (INT, DEFAULT 100)
├── plan (ENUM: 'free', 'starter', 'pro', 'business', DEFAULT 'free')
├── role (ENUM: 'user', 'admin', DEFAULT 'user')
├── stripe_customer_id (VARCHAR 255, NULL)
├── created_at (DATETIME)
└── updated_at (DATETIME)
```

### Subscriptions
```
subscriptions
├── id (INT, AUTO_INCREMENT, PK)
├── user_id (INT, FK → users.id)
├── stripe_subscription_id (VARCHAR 255)
├── plan (ENUM: 'starter', 'pro', 'business')
├── status (ENUM: 'active', 'cancelled', 'past_due', 'paused')
├── credits_per_month (INT)
├── current_period_start (DATETIME)
├── current_period_end (DATETIME)
├── created_at (DATETIME)
└── updated_at (DATETIME)
```

### Credit Packages
```
credit_packages
├── id (INT, AUTO_INCREMENT, PK)
├── name (VARCHAR 100)
├── credits (INT)
├── price_cents (INT)
├── is_active (TINYINT, DEFAULT 1)
├── created_at (DATETIME)
```

### Presentations
```
presentations
├── id (INT, AUTO_INCREMENT, PK)
├── user_id (INT, FK → users.id)
├── title (VARCHAR 255)
├── topic (TEXT)
├── audience (VARCHAR 255)
├── duration_minutes (INT)
├── tone (VARCHAR 100)
├── template_id (VARCHAR 100)
├── status (ENUM: 'draft', 'outline_ready', 'slides_ready', 'audio_ready', 'video_ready', 'exported')
├── created_at (DATETIME)
└── updated_at (DATETIME)
```

### Slides
```
slides
├── id (INT, AUTO_INCREMENT, PK)
├── presentation_id (INT, FK → presentations.id)
├── slide_order (INT)
├── title (VARCHAR 255)
├── content (TEXT)  -- bullet points / body text
├── speaker_notes (TEXT)  -- narration script
├── html_content (MEDIUMTEXT)  -- AI-generated HTML/CSS for rendering
├── image_url (VARCHAR 500)  -- rendered PNG path
├── audio_url (VARCHAR 500)  -- generated TTS audio file
├── layout_type (VARCHAR 50)  -- 'title', 'bullets', 'image_left', 'image_right', etc.
├── created_at (DATETIME)
└── updated_at (DATETIME)
```

### Videos
```
videos
├── id (INT, AUTO_INCREMENT, PK)
├── presentation_id (INT, FK → presentations.id)
├── file_url (VARCHAR 500)
├── file_size_bytes (BIGINT)
├── duration_seconds (INT)
├── resolution (VARCHAR 20, DEFAULT '1920x1080')
├── status (ENUM: 'queued', 'processing', 'complete', 'failed')
├── progress_message (VARCHAR 255, NULL)  -- "Processing slide 3/10..."
├── error_message (TEXT, NULL)
├── created_at (DATETIME)
└── updated_at (DATETIME)
```

### Media Kits
```
media_kits
├── id (INT, AUTO_INCREMENT, PK)
├── presentation_id (INT, FK → presentations.id)
├── user_id (INT, FK → users.id)
├── status (ENUM: 'queued', 'processing', 'complete', 'failed')
├── created_at (DATETIME)
└── updated_at (DATETIME)
```

### Media Kit Assets
```
media_kit_assets
├── id (INT, AUTO_INCREMENT, PK)
├── media_kit_id (INT, FK → media_kits.id)
├── asset_type (ENUM: 'social_post', 'email', 'article', 'press_release', 'landing_page', 'image')
├── platform (VARCHAR 50)  -- 'twitter', 'linkedin', 'facebook', 'instagram', 'youtube', 'email', 'blog'
├── variant (INT, DEFAULT 1)  -- for multiple variants (e.g., 3 tweet options)
├── content_text (MEDIUMTEXT, NULL)  -- text/HTML content
├── file_url (VARCHAR 500, NULL)  -- for image assets
├── dimensions (VARCHAR 20, NULL)  -- '1200x675' for images
├── created_at (DATETIME)
└── updated_at (DATETIME)
```

### Credit Transactions
```
credit_transactions
├── id (INT, AUTO_INCREMENT, PK)
├── user_id (INT, FK → users.id)
├── amount (INT)  -- negative = spent, positive = purchased/bonus
├── action (VARCHAR 100)  -- 'generate_outline', 'generate_slides', 'generate_video', 'purchase_topup', 'subscription_credit', 'signup_bonus'
├── presentation_id (INT, FK → presentations.id, NULL)
├── stripe_payment_id (VARCHAR 255, NULL)  -- for purchases
├── created_at (DATETIME)
```

### Templates
```
templates
├── id (INT, AUTO_INCREMENT, PK)
├── name (VARCHAR 255)
├── thumbnail_url (VARCHAR 500)
├── config_json (JSON)  -- colors, fonts, layout rules, CSS variables
├── is_active (TINYINT, DEFAULT 1)
├── sort_order (INT, DEFAULT 0)
├── created_at (DATETIME)
```

---

## API Endpoints (PHP)

### Auth
- `POST /api/auth/register` — Create account (100 free credits)
- `POST /api/auth/login` — Login (returns session)
- `POST /api/auth/logout` — Destroy session
- `GET /api/auth/me` — Current user info + credits balance

### Presentations
- `GET /api/presentations` — List user's presentations
- `POST /api/presentations` — Create new presentation
- `GET /api/presentations/{id}` — Get presentation with slides
- `PUT /api/presentations/{id}` — Update presentation metadata
- `DELETE /api/presentations/{id}` — Soft delete presentation

### Slides
- `PUT /api/slides/{id}` — Update individual slide (text, notes, HTML)
- `POST /api/slides/reorder` — Reorder slides (drag-and-drop)
- `POST /api/slides/{id}/regenerate` — Regenerate single slide
- `DELETE /api/slides/{id}` — Delete a slide

### AI Generation
- `POST /api/generate/outline` — Generate outline from topic (5 credits)
- `POST /api/generate/slides` — Generate slide content + HTML from outline
- `POST /api/generate/image/{slide_id}` — Generate image for a slide (3 credits)
- `POST /api/generate/audio/{slide_id}` — Generate TTS audio for a slide (2 credits)
- `POST /api/generate/audio/all/{presentation_id}` — Generate audio for all slides
- `POST /api/generate/video/{presentation_id}` — Queue video assembly job (10 credits)

### Export
- `GET /api/export/video/{presentation_id}` — Download MP4 video
- `GET /api/export/pptx/{presentation_id}?type=visual` — Download visual PPTX
- `GET /api/export/pptx/{presentation_id}?type=editable` — Download editable PPTX
- `GET /api/export/pdf/{presentation_id}` — Download PDF

### Media Kit
- `POST /api/media-kit/generate/{presentation_id}` — Generate full media kit (25 credits)
- `POST /api/media-kit/generate/{presentation_id}?type=social` — Social posts only (8 credits)
- `POST /api/media-kit/generate/{presentation_id}?type=emails` — Email sequence only (10 credits)
- `POST /api/media-kit/generate/{presentation_id}?type=article` — Blog article only (8 credits)
- `POST /api/media-kit/generate/{presentation_id}?type=press` — Press release only (5 credits)
- `POST /api/media-kit/generate/{presentation_id}?type=images` — Image set only (10 credits)
- `GET /api/media-kit/{id}` — Get media kit with all assets
- `PUT /api/media-kit/assets/{asset_id}` — Edit asset text
- `GET /api/media-kit/{id}/download` — Download full kit as ZIP
- `GET /api/media-kit/assets/{asset_id}/download` — Download single asset

### Billing (Stripe)
- `GET /api/billing/plans` — List available subscription plans
- `POST /api/billing/subscribe` — Create subscription (Stripe Checkout)
- `POST /api/billing/cancel` — Cancel subscription
- `GET /api/billing/topups` — List available top-up packages
- `POST /api/billing/topup` — Purchase credit top-up (Stripe Checkout)
- `POST /api/billing/webhook` — Stripe webhook handler
- `GET /api/billing/history` — User's payment + credit history

### Video Status
- `GET /api/videos/{id}/status` — Poll video processing status

### Admin
- `GET /api/admin/users` — List all users (with credit balances)
- `GET /api/admin/users/{id}` — User detail + full credit history
- `PUT /api/admin/users/{id}/credits` — Manually adjust user credits
- `GET /api/admin/stats` — Platform stats (users, credits, revenue, presentations)
- `GET /api/admin/transactions` — All credit transactions (with filters)
- `GET /api/admin/transactions/export` — CSV export
- `GET /api/admin/jobs` — Video/media kit processing queue

---

## Frontend Pages

| Page | Description |
|------|------------|
| `/` | Landing page — what is BrightStage, pricing tiers, CTA |
| `/login` | Login form |
| `/register` | Registration form |
| `/dashboard` | User's presentations list + credits balance + "New Presentation" button |
| `/create` | 5-step wizard (content → design → voice/video → export → media kit) |
| `/presentation/{id}` | View/edit existing presentation |
| `/presentation/{id}/media-kit` | View/edit/download media kit assets |
| `/billing` | Current plan, subscription management, top-up purchase |
| `/billing/history` | Payment and credit usage history |
| `/admin` | Admin panel — users, stats, credits, jobs, revenue |

### Frontend approach:
- Server-rendered PHP pages with HTML/CSS
- Tailwind CSS via CDN for styling
- Vanilla JavaScript for interactive parts:
  - Slide editor (inline editing, drag-and-drop reorder)
  - html2canvas slide rendering
  - Progress polling (video/media kit generation)
  - AJAX calls to API endpoints
- No SPA framework — traditional multi-page with JS enhancement

---

## Security

- Passwords hashed with `password_hash()` (bcrypt)
- All SQL queries use prepared statements (PDO) — no exceptions
- Session-based auth with CSRF tokens on all forms
- File uploads validated (type whitelist, max size, extension check)
- User can only access their own resources (`WHERE user_id = ?` on every query)
- Admin role check on all admin endpoints
- API keys (OpenRouter, Stripe) in `.env` file only — never in code, never in git
- Rate limiting on AI generation endpoints (prevent credit abuse)
- Sanitize all user input before rendering (`htmlspecialchars()`)
- Never expose internal errors to users (generic error messages)
- Credit balance verified BEFORE any AI/generation call (prevent negative balance)

---

## Phase Plan

### Phase 1: Foundation (MVP)
- User auth (register/login/logout)
- Database setup (all tables)
- Dashboard (list presentations, show credits)
- Create presentation wizard Step 1 (content input)
- AI outline generation via OpenRouter
- Basic slide editor (text editing)

### Phase 2: Slides & Design
- AI HTML/CSS slide generation (SurfSense-quality approach)
- html2canvas rendering in browser → PNG
- Template system (3-5 coded templates to start)
- Slide editor (edit text, reorder, add/remove)
- AI image generation per slide
- PDF export

### Phase 3: Voice & Video
- TTS integration (via OpenRouter or dedicated API)
- Audio generation per slide
- Server-side video assembly (FFmpeg batch pipeline)
- Video progress polling
- Video download
- Visual PPTX export (image-based)
- Editable PPTX export (PHPPresentation)

### Phase 4: Media Kit
- Written asset generation (social posts, emails, articles, press release)
- Image asset generation (all platform sizes via html2canvas)
- Media kit dashboard (preview, edit, download)
- Individual asset download + full ZIP download
- Inline text editing for generated copy

### Phase 5: Monetization & Admin
- Stripe subscription integration (4 tiers)
- Credit top-up packages
- Stripe webhook handling (payment confirmations, subscription changes)
- Admin dashboard (users, credits, revenue, stats)
- Credit tracking + transaction history
- CSV export for admin
- Manual credit adjustment for admin

### Phase 6: Polish & Launch
- Landing page with pricing
- Performance optimization
- Security hardening
- Error handling & user-friendly messages
- Email notifications (welcome, payment confirmation, video ready)
- Share links for presentations

---

## Confirmed Details

1. **Hosting:** cPanel shared hosting at ai.brightstageai.com
2. **AI Provider:** OpenRouter (multi-model access)
3. **Git:** github.com/bigchain/brightstageai (private)
4. **Database:** MySQL via phpMyAdmin
5. **Slide approach:** AI HTML/CSS → html2canvas → PNG (SurfSense quality)
6. **PPTX approach:** Dual output — visual (images) + editable (PHPPresentation)
7. **Video approach:** Server-side FFmpeg batch (per-slide segments → concat)
8. **Payments:** Stripe subscriptions + credit top-ups
9. **No frameworks:** PHP + vanilla JS, no React/Vue/Angular, no npm, no build tools

## Remaining Open Questions

1. **TTS provider:** Which service for voice narration? (OpenRouter TTS, ElevenLabs, or other?)
2. **FFmpeg on hosting:** Does your cPanel host have FFmpeg installed? (Critical — may need VPS if not)
3. **Max presentation length:** Cap at 30 min? 60 min? Unlimited?
4. **Composer on hosting:** Is Composer available on cPanel? (Needed for PHPPresentation library)

---

## Success Metrics

- **Works on all devices** (no more mobile crashes)
- **Under 5 seconds** page load
- **95%+ video generation success rate** (vs old 60%)
- **Full presentation in under 10 minutes** (topic to video)
- **Media kit generated in under 2 minutes** (all assets)
- **Designer-quality slides** (SurfSense-level via HTML/CSS rendering)
- **< 5,000 lines of PHP code** (vs old 25,000 lines)
