# BrightStage Video — Design System & UX Flow

**Last updated:** 2026-04-08
**Live at:** https://ai.brightstageai.com

---

## Current State

- **38 files**, ~3,900 lines of PHP/JS
- **Stack:** PHP 8 + MySQL + vanilla JS + Tailwind CDN + OpenRouter AI
- **Hosting:** cPanel shared hosting (ai.brightstageai.com)
- **Git:** github.com/bigchain/brightstageai (public)

---

## UX Flow — Complete User Journey

### Landing Page (`/`)
- Hero: "Topic In. Video Out." — large bold heading
- 5-step visual process bar (Topic → Design → Voice → Export → Media Kit)
- Feature cards (6 cards: Video, Emails, Social Images, Articles, PPTX, Credits)
- Pricing section (4 tiers: Free/Starter/Pro/Business)
- CTA: "Start Free — 100 Credits"

### Registration (`/register`)
- Name, email, password, confirm password
- Auto-login after register
- 100 free credits granted
- Redirect to dashboard

### Login (`/login`)
- Email + password
- Session-based auth (bcrypt + CSRF)

### Dashboard (`/dashboard`)
- Credit balance banner (gradient blue, shows plan + credits)
- Presentation cards grid (3 columns)
- Each card shows: status badge, title, topic preview, slide count, date
- **Actions per card:** Edit | Duplicate | Rename | Delete
- "+ New Presentation" button

### Create Wizard (`/create`) — 3-Step Single-Page Flow

**Step 1: Topic**
- Large textarea: "What's your presentation about?"
- **AI Enhance button** (purple, top-right of textarea)
  - User types brief idea (e.g., "dog training")
  - Click AI Enhance → AI returns: title, description, audience
  - Description replaces textarea content
  - Title + Audience fields appear below (editable)
- Duration selector (5/10/15/30 min)
- Tone selector (Professional/Casual/Academic/Inspirational/Technical/Sales)
- "Generate Slides & Narration" button (separate from enhance)

**Step 2: Preview**
- All slides listed vertically
- Each slide card shows:
  - Slide number (circle badge) + layout type dropdown
  - **AI Polish** button (purple) — improves grammar, sharpens bullets, smooths narration
  - **Remove** button
  - Slide Title (editable input)
  - Two columns: **Slide Content** (left) | **Narration Script** (right)
- "+ Add Slide" button (top + dashed bottom)
- Layout types: Title, Bullets, Quote, Image Left, Image Right, Two Column
- "Looks Good — Choose Design →" button

**Step 3: Design**
- 7 template cards in a row:
  1. **Clean White** — white bg, blue accent, Inter font
  2. **Light Elegant** — off-white bg, purple accent, Playfair Display font
  3. **Corporate** — navy gradient, blue accent, Inter font
  4. **Creative** — red/purple gradient, yellow accent, Poppins font
  5. **Minimal** — dark gray, green accent, Raleway font
  6. **Dark** — black, red accent, Raleway font
  7. **Vibrant** — blue/pink gradient, Montserrat font
- **Live preview panel** — two 16:9 slide mockups:
  - Title slide: with badge, divider, decorative shapes
  - Bullets slide: numbered bullets, accent stripe, corner decoration
  - Updates instantly when clicking templates
- "Create Presentation" button → deducts credits → redirects to editor

### Presentation Editor (`/presentation/{id}`)
- Breadcrumb: Dashboard / Status Badge
- Title, audience, duration, tone display
- **Actions:** + Add Slide | Duplicate | Delete
- Progress bar (shown during AI generation)
- Each slide card:
  - Slide preview image (if rendered)
  - Status badges: "designed" (purple), "rendered" (green)
  - Slide number + layout type
  - Save Changes / Remove buttons
  - Title input, Content textarea, Speaker Notes textarea
- Bottom bar:
  - Save All Changes
  - **Design Slides** button (purple) — AI generates HTML/CSS per slide
  - **Render Slides to Images** button (green) — html2canvas captures PNGs

---

## Visual Design Language

### Colors
- **Brand primary:** `#3b82f6` (blue-500)
- **Brand dark:** `#1e3a5f` (navy)
- **Background:** `#f9fafb` (gray-50)
- **Cards:** White with `border-gray-200`
- **Text primary:** `#111827` (gray-900)
- **Text secondary:** `#6b7280` (gray-500)
- **Success:** green-600
- **Error:** red-600
- **Accent actions:** purple-600 (AI features)

### Typography
- **Framework:** Tailwind CSS via CDN
- **Body font:** System stack (via Tailwind defaults)
- **Template fonts:** Inter, Poppins, Playfair Display, Raleway, Montserrat (Google Fonts loaded)
- **Headings:** font-bold, text-2xl for page titles
- **Body text:** text-sm (14px)
- **Labels:** text-xs, font-medium, text-gray-500, uppercase tracking-wide

### Components
- **Cards:** `rounded-xl border border-gray-200 bg-white` with hover: `shadow-sm`
- **Buttons:**
  - Primary: `bg-brand-600 text-white rounded-lg px-5 py-2.5`
  - Secondary: `border border-gray-300 text-gray-700 bg-white rounded-lg`
  - Danger: `border border-red-200 text-red-600 bg-white`
  - AI action: `bg-purple-600 text-white` (for AI Enhance, AI Polish, Design Slides)
  - Success: `bg-green-600 text-white` (for Render, Create Presentation)
- **Status badges:** `rounded-full px-2.5 py-0.5 text-xs font-medium` with color variants
- **Inputs:** `border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-brand-500`
- **Progress bar:** `bg-gray-200 rounded-full h-2` with `bg-brand-600` fill
- **Progress overlay:** Fixed full-screen dark overlay with white card, spinning loader
- **Flash messages:** `rounded-md bg-green-50/bg-red-50 border p-4` — auto-dismiss after 5s

### Spacing & Layout
- Max width: `max-w-7xl` (dashboard), `max-w-4xl` (create wizard), `max-w-3xl` (auth forms)
- Page padding: `px-4 sm:px-6 lg:px-8 py-10`
- Card padding: `p-6` or `p-8`
- Section spacing: `space-y-6` or `mb-8`
- Grid: `grid-cols-3` for dashboard cards, `grid-cols-7` for templates

### Animations & Transitions
- Cards: `transition: all 0.2s ease` with `hover: translateY(-2px)`
- Template preview: `transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1)`
- Flash messages: auto-dismiss after 5s
- Slide cards: green ring flash on save/polish success
- Progress overlay: fade in/out

---

## Navigation

- **Navbar:** Sticky top, white bg, border-bottom
- **Left:** BrightStage (logo/link) + Dashboard + "+ New"
- **Right:** Credit badge (pill, bg-brand-100) + User name + Sign Out
- **Mobile:** Not yet optimized (future task)

---

## File Structure

```
public/
├── index.php              # Router (all requests)
├── .htaccess              # URL rewriting
└── assets/js/
    └── slide-renderer.js  # html2canvas slide rendering

src/
├── config/                # env, database, app constants
├── controllers/           # Auth, Dashboard, Presentation, API controllers
├── models/                # User, Presentation, Slide (PDO queries)
├── services/              # OpenRouter, Outline, SlideGeneration, TopicEnhancer, SlidePolish
├── middleware/             # Session auth, rate limiting
├── helpers/               # CSRF, flash, rendering, auth checks
└── templates/
    ├── layouts/base.php   # HTML shell, nav, footer, global JS
    └── pages/             # home, login, register, dashboard, create, presentation, 404

database/
└── migrations/            # Numbered SQL files for phpMyAdmin
```

---

## What's Built (Phases 1-2)

| Feature | Status |
|---------|--------|
| Auth (register/login/logout) | Done |
| Dashboard with credit tracking | Done |
| 3-step create wizard | Done |
| AI topic enhancement | Done |
| AI outline generation (slides + narration) | Done |
| AI slide polish (per-slide) | Done |
| Add/remove/reorder slides | Done |
| 7 design templates | Done |
| Live template preview (Beautiful.ai style) | Done |
| Project management (edit/duplicate/rename/delete) | Done |
| AI HTML/CSS slide generation | Done |
| html2canvas PNG rendering | Done |
| Security audit (2 rounds) | Done |

## What's Next (Phases 3-6)

| Feature | Phase |
|---------|-------|
| TTS audio generation (per slide) | Phase 3 |
| FFmpeg video assembly (server-side) | Phase 3 |
| Visual PPTX export (image-based) | Phase 3 |
| Editable PPTX export (PHPPresentation) | Phase 3 |
| PDF export | Phase 3 |
| Media Kit generator (social posts, emails, articles, images) | Phase 4 |
| Stripe subscriptions + credit top-ups | Phase 5 |
| Admin dashboard (users, credits, revenue) | Phase 5 |
| Landing page polish + mobile responsive | Phase 6 |
| Email notifications | Phase 6 |
