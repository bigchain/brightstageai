# BrightStage Video

AI platform: topic in → video presentation + full marketing media kit out.
**Stack:** PHP 8+ · MySQL · vanilla JS · OpenRouter · FFmpeg · Stripe

---

## Tech Stack (Non-Negotiable)

| Layer | Tech | Note |
|-------|------|------|
| Backend | PHP 8+ | cPanel shared hosting at ai.brightstageai.com |
| Database | MySQL | Managed via phpMyAdmin |
| Frontend | HTML + CSS + vanilla JS | Tailwind via CDN. No frameworks. No build tools. |
| AI/LLM | OpenRouter API | Multi-model (GPT-4, Claude, etc.) |
| Slides | AI → HTML/CSS → html2canvas → PNG | SurfSense-quality rendering in browser |
| Video | Server-side FFmpeg | Batch: per-slide segments → concat → final MP4 |
| PPTX | PHPPresentation (phpoffice) | Dual: visual (image-based) + editable (real text) |
| Payments | Stripe | Subscriptions + credit top-ups |
| Storage | Server filesystem | /storage/users/{user_id}/presentations/{pres_id}/ |
| Git | github.com/bigchain/brightstageai | Private repo |

**Banned technologies:** React, Vue, Angular, TypeScript, npm, Vite, Supabase, Firebase, WordPress, client-side FFmpeg, Remotion. If you think we need any of these — we don't. Ask first.

---

## Key Commands

```bash
# There is no build step. PHP files run directly.
# Database: manage via phpMyAdmin at cPanel
# Cron: video worker runs every 30s (configured in cPanel)
# Logs: cPanel → Error Logs
# Deploy: git push to main → pull on server (or cPanel File Manager)
# FFmpeg check: shell_exec('ffmpeg -version')
```

---

## Project Structure

```
/
├── CLAUDE.md              # You are here
├── SPEC_REVIEW.md         # Full product spec (READ THIS for context)
├── .env                   # Credentials (NEVER commit)
├── .env.example           # Safe template (committed)
├── .gitignore             # Configured
│
├── public/                # Web root (DocumentRoot points here)
│   ├── index.php          # Landing page
│   ├── assets/            # CSS, JS, images
│   │   ├── css/
│   │   ├── js/
│   │   └── img/
│   └── api/               # API entry point
│       └── index.php      # Router
│
├── src/                   # PHP application code
│   ├── config/            # Database connection, env loader, constants
│   ├── controllers/       # Request handlers (thin — call services)
│   ├── services/          # Business logic (AI, video, media kit, billing)
│   ├── models/            # Database queries (PDO prepared statements)
│   ├── middleware/         # Auth check, CSRF, rate limiting
│   ├── helpers/           # Small utility functions
│   └── templates/         # PHP HTML templates (views)
│       ├── layouts/       # Base layout (header, footer, nav)
│       ├── pages/         # Full pages (dashboard, create, billing, admin)
│       └── partials/      # Reusable components (slide card, credit badge)
│
├── storage/               # User-generated files (gitignored)
│   └── users/{id}/presentations/{id}/
│       ├── slides/        # PNG renders
│       ├── audio/         # TTS MP3s
│       ├── video/         # Segments + final MP4
│       └── media-kit/     # Generated assets
│
├── workers/               # Background job scripts
│   └── video_worker.php   # Cron job: process queued videos
│
├── thedoc/                # Old analysis docs (reference only, don't modify)
└── vendor/                # Composer packages (gitignored)
```

---

## Mandatory Rules

1. **Credentials in `.env` only.** Never hardcode API keys, passwords, or secrets. Never commit `.env`. Check before every commit.
2. **All SQL via PDO prepared statements.** No string concatenation in queries. No exceptions. Ever.
3. **Every endpoint verifies ownership.** `WHERE user_id = ?` on every query touching user data.
4. **Check credit balance BEFORE any AI call.** Deduct AFTER success. Log every transaction to `credit_transactions`.
5. **No frameworks.** Vanilla JS on the frontend. Plain PHP on the backend. If you think you need a framework — you don't.
6. **No AI-generated executable code.** AI outputs data (JSON) and visual markup (HTML/CSS). Never generate PHP or JS to eval/execute.
7. **Max 2 files per change.** If a change touches more than 2 files, break into separate verified passes.

---

## Coding Conventions

### PHP
- `snake_case` for functions and variables: `get_user_credits()`, `$slide_order`
- `PascalCase` for classes: `PresentationService`, `CreditModel`
- One class per file, filename matches class name
- Return early, avoid deep nesting
- Type hints on function parameters and returns where PHP supports it

### JavaScript
- `camelCase` for functions and variables
- No jQuery. No libraries except `html2canvas` (for slide rendering) and Stripe.js (for payments)
- Use `fetch()` for AJAX, not XMLHttpRequest
- Use `async/await`, not callback chains

### API Responses
Every endpoint returns this shape:
```json
{"success": true, "data": { ... }}
{"success": false, "error": "Human-readable message"}
```
Never expose stack traces, SQL errors, or internal paths to the user.

### File Organization
- Controllers are thin — validate input, call service, return response
- Services contain business logic — AI calls, video pipeline, credit checks
- Models contain database queries only — no business logic
- Helpers are stateless pure functions

---

## Core Patterns

### Slide Rendering Pipeline
```
AI (OpenRouter) → HTML/CSS per slide → browser html2canvas → PNG → server storage
```
The AI generates beautiful HTML/CSS (gradients, Google Fonts, modern layouts). The browser renders it. We capture the image. No headless browser needed.

### Video Assembly Pipeline
```
Slide PNGs + TTS Audio → FFmpeg per-slide segments → FFmpeg concat → final MP4
```
Background PHP worker (cron every 30s) picks up queued jobs. Frontend polls for status.

### Credit Flow
```
Check balance → sufficient? → call AI/service → success? → deduct credits → log transaction
```
Never deduct before success. Never allow negative balance.

### Auth Flow
```
PHP sessions + bcrypt passwords + CSRF token on every form
```

---

## Patterns We AVOID

- **No client-side video processing** — this crashed 40% of mobile users in the old version
- **No SPA routing** — server-rendered pages, JS enhances interactivity only
- **No over-engineering** — if it works in 10 lines, don't make it 50
- **No premature abstraction** — 3 similar lines > 1 clever utility nobody understands
- **No unused code** — dead code gets deleted, not commented out
- **No TODO/FIXME in code** — fix it now or file it in SPEC_REVIEW.md open questions

---

## Security Rules

- `password_hash()` with bcrypt for all passwords
- `htmlspecialchars()` on ALL user input before rendering
- CSRF token on every POST form
- File upload validation: type whitelist, max size, extension check
- Rate limiting on AI generation endpoints (prevent credit abuse)
- Admin endpoints check `role = 'admin'` — no shortcuts
- Never log API keys, passwords, tokens, or PII
- `.env` in `.gitignore` — verified and enforced

---

## Git Conventions

- **Branches:** `main` (production), `feature/*`, `fix/*`
- **Commits:** conventional style → `feat: add media kit generator`, `fix: credit deduction on failed video`
- **Never commit:** `.env`, `storage/`, `vendor/`, user uploads
- **Never force push to main**
- `.gitignore` is already configured — trust it but verify

---

## Environment Requirements

- PHP 8.0+
- MySQL 5.7+
- FFmpeg installed on server (verify: `shell_exec('ffmpeg -version')`)
- Composer (for PHPPresentation library)
- OpenRouter API key in `.env`
- Stripe API keys in `.env` (test mode for dev, live for prod)
- cPanel cron job configured for video worker

---

## Before ANY Task

1. **Read this file** — you're holding the rules
2. **Read SPEC_REVIEW.md** if the task touches features, DB schema, or credit costs
3. **Search existing code first** — don't create what already exists
4. **Ask 3 hard questions** before writing code:
   - "What breaks if I change this?"
   - "Does this touch credits, auth, or user data?"
   - "Is there existing code that does this already?"
5. **Verify credit math** on any generation endpoint change
6. **Test the change** — don't build on top of known bugs

---

## Scope Control

- **NEVER** modify unrelated code — flag it, don't fix it
- **NEVER** add dependencies without asking (no npm, no new composer packages)
- **NEVER** create files when existing ones should be edited
- **NEVER** "improve" working code that isn't part of the current task
- **NEVER** change test expectations to make tests pass — fix the implementation

---

## Architecture References

| Doc | Purpose |
|-----|---------|
| `SPEC_REVIEW.md` | Full product spec — features, DB schema, API endpoints, credit costs, phase plan |
| `thedoc/` | Old codebase analysis — reference only, do not modify |
| `.env.example` | Environment variable template |

---

## Quick Reference: Credit Costs

| Action | Credits |
|--------|---------|
| Outline | 5 |
| Slide content (each) | 2 |
| Slide image (each) | 3 |
| Slide audio (each) | 2 |
| Video assembly | 10 |
| Visual PPTX | 3 |
| Editable PPTX | 5 |
| PDF | 3 |
| Full media kit | 25 |

Full 10-slide presentation + media kit ≈ 110 credits
su - convertpods -c "ln -sf /home/convertpods/brightstageai/storage /home/convertpods/brightstageai/public/storage"
su - convertpods -c "cd ~/brightstageai && git pull"
