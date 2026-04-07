# 🎯 UX & Logic Flow Documentation
**BrightStage AI - Complete User Experience & Application Flows**  
Created: 2025-10-07 00:08:29

---

## 📊 Overview

This document maps all user journeys, application logic flows, state management, and data flows through the BrightStage AI platform.

---

## 🚀 Primary User Flows

### Flow 1: Authentication & Onboarding

```
┌─────────────────────────────────────────────────────────────┐
│ USER LANDS ON APP                                            │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
        ┌────────────────┐
        │ App.tsx Loads  │
        └────────┬───────┘
                 │
                 ▼
        ┌────────────────────┐
        │ useAuth() Hook     │
        │ - Check session    │
        │ - Load user data   │
        └────────┬───────────┘
                 │
         ┌───────┴────────┐
         │                │
         ▼                ▼
    NOT AUTH         AUTHENTICATED
         │                │
         ▼                ▼
┌─────────────────┐  ┌────────────────┐
│ Login Screen    │  │ Dashboard      │
│ - Sign In       │  │ - Load         │
│ - Get 100       │  │   projects     │
│   free tokens   │  │ - Show         │
└─────────────────┘  │   balance      │
                     └────────────────┘
```

**Implementation Files:**
- `src/App.tsx` (main router)
- `src/hooks/useAuth.ts` (authentication logic)
- `src/components/Dashboard.tsx` (authenticated home)

**State Management:**
```typescript
AuthState {
  user: User | null
  isLoading: boolean
  isAuthenticated: boolean
}
```

**Triggered Actions:**
1. `useAuth()` → Check `supabase.auth.getSession()`
2. If authenticated → `loadUser()` → Database query
3. If not → Show login screen
4. On login → `supabase.auth.signIn()` → Redirect to Dashboard

---

### Flow 2: Webinar Creation Journey (4-Step Process)

```
┌──────────────────────────────────────────────────────────────┐
│ STEP 1: CONTENT INPUT                                         │
├──────────────────────────────────────────────────────────────┤
│ User Actions:                                                 │
│ 1. Enter topic                                                │
│ 2. Define audience                                            │
│ 3. Select duration (30-120 min)                              │
│ 4. Write description                                          │
│ 5. [Optional] Enhance with AI                                │
│ 6. [Optional] Generate outline                               │
│                                                                │
│ Logic Flow:                                                   │
│ handleEnhance() → AIService.enhanceDescription()             │
│ handleGenerateOutline() → AIService.generateOutline()        │
│                                                                │
│ State Updates:                                                │
│ webinarData.topic ────┐                                      │
│ webinarData.audience ─┼─→ updateWebinarData() → Auto-save   │
│ webinarData.outline ──┘                                      │
└──────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────┐
│ STEP 2: SLIDE DESIGN                                          │
├──────────────────────────────────────────────────────────────┤
│ User Actions:                                                 │
│ 1. Review generated outline                                   │
│ 2. Select slide template                                      │
│ 3. Generate slides from outline                              │
│ 4. Edit individual slides                                     │
│ 5. [Optional] Download/upload PPTX                           │
│                                                                │
│ Logic Flow:                                                   │
│ handleGenerateSlides() → SlideGenerator.generate()           │
│   → Creates slides from outline                              │
│   → Applies selected template                                │
│   → Adds visual elements                                     │
│                                                                │
│ State Updates:                                                │
│ webinarData.template ──┐                                     │
│ webinarData.slides[] ──┼─→ updateWebinarData() → Auto-save  │
└──────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────┐
│ STEP 3: VOICE & VIDEO                                         │
├──────────────────────────────────────────────────────────────┤
│ User Actions:                                                 │
│ 1. Select voice style (professional/enthusiastic/calm)       │
│ 2. Choose TTS provider                                        │
│ 3. Preview voice sample                                       │
│ 4. Generate script (AI writes narration)                     │
│ 5. Edit script if needed                                      │
│ 6. Generate full video                                        │
│                                                                │
│ Logic Flow:                                                   │
│ handleGenerateScript() → AIService.generateScript()          │
│ handleGenerateVideo() → VideoGenerationService.generate()    │
│   ├─ Generate audio (TTSService)                             │
│   ├─ Process slides to images                                │
│   ├─ Assemble video (FFmpeg or Server)                       │
│   └─ Upload & return URL                                     │
│                                                                │
│ Progress Tracking:                                            │
│ onProgress callback → Update UI (0% → 100%)                  │
└──────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────┐
│ STEP 4: EXPORT & SHARE                                        │
├──────────────────────────────────────────────────────────────┤
│ User Actions:                                                 │
│ 1. Watch final video                                          │
│ 2. Generate pitch video (condensed version)                  │
│ 3. Export in formats (MP4, PPTX, PDF)                        │
│ 4. Share via link or download                                │
│                                                                │
│ Logic Flow:                                                   │
│ handleExport() → PPTXGenerator.generate()                    │
│ handleGeneratePitch() → Create 2-min highlight reel          │
│                                                                │
│ File Operations:                                              │
│ Export → Generate file → Supabase Storage → Return URL      │
└──────────────────────────────────────────────────────────────┘
                              │
                              ▼
                     ┌────────────────┐
                     │ Back to        │
                     │ Dashboard      │
                     └────────────────┘
```

---

## 🔄 State Management Flow

### WebinarCreator Component State

```typescript
Component State:
┌─────────────────────────────────────────┐
│ WebinarCreator                           │
├─────────────────────────────────────────┤
│ currentStep: 1-4                         │
│ webinarData: {                           │
│   topic, audience, duration,             │
│   description, outline, slides,          │
│   script, videoUrl, etc.                 │
│ }                                        │
│ currentWebinarId: string                 │
│ lastSaved: Date                          │
└───────────┬─────────────────────────────┘
            │
            ├─→ Auto-save every 30s
            ├─→ Save on step change
            ├─→ Save on Ctrl+S
            └─→ Save before exit
```

**State Update Flow:**
```
User Input
    │
    ▼
updateWebinarData(updates)
    │
    ├─→ setWebinarData(prev => ({...prev, ...updates}))
    │
    ├─→ Trigger debounced auto-save
    │
    └─→ useWebinarProject.updateProject(id, updates)
            │
            ▼
        Database Update
            │
            ▼
        Supabase RPC call
            │
            ▼
        projects table updated
```

---

## 📡 Data Flow Architecture

### Layer Communication

```
┌───────────────────────────────────────────────────────────┐
│ PRESENTATION LAYER (Components)                            │
│ Dashboard, WebinarCreator, ContentInputStep, etc.         │
└───────────────────┬───────────────────────────────────────┘
                    │
                    ▼ Uses Hooks
┌───────────────────────────────────────────────────────────┐
│ HOOKS LAYER (State Management)                             │
│ useAuth, useWebinarProject, useAsyncState                 │
└───────────────────┬───────────────────────────────────────┘
                    │
                    ▼ Calls Services
┌───────────────────────────────────────────────────────────┐
│ SERVICES LAYER (Business Logic)                            │
│ AIService, TTSService, VideoGenerationService             │
└───────────────────┬───────────────────────────────────────┘
                    │
                    ▼ Uses Utils/DB
┌───────────────────────────────────────────────────────────┐
│ DATA LAYER                                                 │
│ DatabaseService, Supabase Client, API Wrappers           │
└───────────────────┬───────────────────────────────────────┘
                    │
                    ▼
┌───────────────────────────────────────────────────────────┐
│ EXTERNAL SERVICES                                          │
│ Supabase, OpenAI, ElevenLabs, Stripe, Storage            │
└───────────────────────────────────────────────────────────┘
```

---

## 🎬 Video Generation Flow (Detailed)

```
handleGenerateVideo()
    │
    ├─→ Validate: slides exist, script exists
    │
    ├─→ Create VideoGenerationService instance
    │
    ├─→ service.generate(webinarData, options, onProgress)
        │
        ├─ STAGE 1: Preparing (0-10%)
        │   └─ Validate inputs, check resources
        │
        ├─ STAGE 2: Generate Audio (10-40%)
        │   ├─ TTSService.generateAudio(script, voice)
        │   ├─ Split script by slides
        │   ├─ Generate audio for each section
        │   └─ Upload to Supabase Storage → audioUrl
        │
        ├─ STAGE 3: Process Slides (40-60%)
        │   ├─ For each slide:
        │   │   ├─ Render to canvas/image
        │   │   ├─ Apply template styling
        │   │   └─ Upload to Supabase Storage
        │   └─ Return slideImageUrls[]
        │
        ├─ STAGE 4: Assemble Video (60-95%)
        │   ├─ [CLIENT] FFmpegService.assemble()
        │   │   OR
        │   └─ [SERVER] Edge Function: /api/video/assemble
        │       ├─ Combine audio + slide images
        │       ├─ Add transitions
        │       ├─ Encode to MP4
        │       └─ Upload final video
        │
        └─ STAGE 5: Finalize (95-100%)
            ├─ Get video metadata (duration, size)
            ├─ Update project record
            └─ Return videoUrl
```

**Files Involved:**
- `VideoGenerationService.ts` (orchestrator)
- `TTSService.ts` (audio generation)
- `SlideGenerator.ts` (slide rendering)
- `FFmpegService.ts` (video assembly - client)
- Edge Function (video assembly - server)

---

## 💾 Database Operations Flow

### Create Project

```
User clicks "Create Webinar"
    │
    ▼
Dashboard.onCreateWebinar()
    │
    ▼
App sets currentView = 'creator'
    │
    ▼
WebinarCreator loads
    │
    ▼
useWebinarProject.createProject()
    │
    ├─→ Generate unique ID
    ├─→ Build project object
    ├─→ db.webinarProjects.create()
    │       │
    │       ▼
    │   Supabase RPC
    │       │
    │       ▼
    │   INSERT INTO webinar_projects
    │       │
    │       ▼
    │   RLS Policy Check (user owns project)
    │       │
    │       ▼
    │   Return created project
    │
    └─→ Update local state: setProjects([new, ...prev])
```

### Update Project (Auto-save)

```
User edits webinar data
    │
    ▼
updateWebinarData() called
    │
    ▼
30s debounce timer
    │
    ▼
handleSave(silent=true)
    │
    ▼
useWebinarProject.updateProject(id, updates)
    │
    ├─→ db.webinarProjects.update()
    │       │
    │       ▼
    │   UPDATE webinar_projects SET ... WHERE id = ?
    │       │
    │       ▼
    │   Trigger: update_updated_at()
    │       │
    │       ▼
    │   Return updated project
    │
    └─→ Update local state + lastSaved timestamp
```

---

## 🔐 Authentication Flow

```
┌─────────────────────────────────────────┐
│ User clicks "Sign In"                    │
└───────────┬─────────────────────────────┘
            │
            ▼
useAuth.login()
    │
    ├─→ supabase.auth.signInWithOAuth({ provider: 'google' })
    │       │
    │       ▼
    │   Redirect to Google OAuth
    │       │
    │       ▼
    │   User authenticates
    │       │
    │       ▼
    │   Callback to app
    │
    ├─→ supabase.auth.onAuthStateChange() triggered
    │       │
    │       ▼
    │   Get session & user data
    │
    ├─→ Database Trigger: handle_new_user()
    │       │
    │       ▼
    │   INSERT INTO users (id, email, tokens_balance=1000)
    │
    └─→ Update state: setAuthState({ user, isAuthenticated: true })
            │
            ▼
        App re-renders → Show Dashboard
```

---

## 🎨 UI Interaction Flows

### Keyboard Shortcuts

```
WebinarCreator Keyboard Handlers:

Ctrl+S → handleSave() → Save current progress
Ctrl+→ → handleStepChange(currentStep + 1)
Ctrl+← → handleStepChange(currentStep - 1)
Escape → onBack() → Return to Dashboard

Implementation:
window.addEventListener('keydown', handleKeyDown)
```

### Progress Tracking

```
Video Generation Progress:

VideoGenerationService emits progress events:
    │
    ├─ { stage: 'preparing', progress: 0, message: '...' }
    ├─ { stage: 'generating_audio', progress: 20, message: '...' }
    ├─ { stage: 'processing_slides', progress: 50, message: '...' }
    ├─ { stage: 'assembling_video', progress: 80, message: '...' }
    └─ { stage: 'completed', progress: 100, message: '...' }
        │
        ▼
    onProgress callback
        │
        ▼
    Update UI:
    <Progress value={progress} />
    <p>{message}</p>
```

---

## 🚨 Error Handling Flow

```
Error occurs anywhere in app
    │
    ▼
try/catch block catches error
    │
    ├─→ Log to console
    ├─→ ErrorService.logError(error, context, severity)
    │       │
    │       ▼
    │   Store in error_logs table
    │   Rate limit: max 10 errors/minute
    │   Deduplicate: same error within 5 min
    │
    ├─→ Show user-friendly message
    │   toast({ title: 'Error', description: message })
    │
    └─→ [If critical] Trigger alert
        │
        ▼
    ErrorService.triggerCriticalAlert()
        │
        ▼
    Send to monitoring service (Sentry, etc.)
```

---

## 🔄 Component Lifecycle Flows

### WebinarCreator Mount → Unmount

```
Component Mounts
    │
    ├─→ useEffect: Load existing webinar if editingWebinarId
    ├─→ useEffect: Set up auto-save timer (30s)
    ├─→ useEffect: Add keyboard event listeners
    └─→ useWebinarProject: Load projects from DB
        │
        ▼
    Component renders with initial state
        │
        ▼
    User interacts → State updates
        │
        ▼
    Auto-save triggers periodically
        │
        ▼
    Component Unmounts
        │
        ├─→ Cleanup auto-save timer
        ├─→ Remove keyboard listeners
        └─→ Final save (if has changes)
```

---

## 📊 Token Usage Flow

```
AI Operation Triggered (e.g., generate outline)
    │
    ▼
AIService.generateOutline()
    │
    ├─→ Estimate cost: AIService.estimateTokenCost()
    │
    ├─→ Check user balance: useAuth.user.tokensBalance
    │       │
    │       ├─ Insufficient → Show "Buy Tokens" modal
    │       └─ Sufficient → Proceed
    │
    ├─→ Call AI API (OpenAI/Claude/etc.)
    │       │
    │       ▼
    │   Receive response + token usage
    │
    └─→ Record transaction
        │
        ├─→ db.tokenTransactions.create({
        │     userId, amount: -tokensUsed,
        │     type: 'usage', description: 'Generated outline'
        │   })
        │
        └─→ Update user balance
            │
            ▼
        db.users.update({
          tokensBalance: currentBalance - tokensUsed
        })
```

---

## 🎯 Complete User Journey Map

```
NEW USER JOURNEY:
═══════════════════

1. Land on app → See login screen
2. Click "Sign In" → OAuth flow → Get 100 free tokens
3. Dashboard loads → See "Create New Webinar" button
4. Click create → WebinarCreator Step 1
5. Fill in topic, audience, duration
6. Click "Enhance" → AI improves description (costs 5 tokens)
7. Click "Generate Outline" → AI creates structure (costs 10 tokens)
8. Review outline → Click "Next" → Step 2
9. Select template → Click "Generate Slides" (costs 20 tokens)
10. Review slides → Edit if needed → Click "Next" → Step 3
11. Select voice → Click "Generate Script" (costs 15 tokens)
12. Review script → Click "Generate Video" (costs 30 tokens)
13. Wait for video (progress bar updates)
14. Video complete → Click "Next" → Step 4
15. Watch video → Download PPTX/PDF → Share link
16. Click "Back to Dashboard" → See completed project

TOKENS USED: 80 tokens (20 remaining)
```

---

## 📐 Architecture Decision Flow

```
Which Video Generator to Use?

User triggers video generation
    │
    ▼
Check device capabilities
    │
    ├─→ Mobile device? → Use Server Strategy
    ├─→ Low memory? → Use Server Strategy
    ├─→ Fast connection? → Use Server Strategy
    └─→ Otherwise → Use Client Strategy (FFmpeg)
        │
        ▼
VideoGenerationService(strategy)
    │
    ├─ ServerVideoStrategy → Call Edge Function
    │       │
    │       ▼
    │   POST /api/video/assemble
    │   { audioUrl, slideUrls, options }
    │       │
    │       ▼
    │   Server processes with unlimited resources
    │       │
    │       ▼
    │   Return videoUrl
    │
    └─ ClientVideoStrategy → Use FFmpeg.wasm
        │
        ▼
    Download FFmpeg (~27MB)
        │
        ▼
    Process in browser
```

---

## 📝 Summary

**Total Flows Documented:** 15+
- Authentication Flow
- 4-Step Webinar Creation Flow
- State Management Flow
- Data Flow Architecture
- Video Generation Flow
- Database Operations Flow
- Error Handling Flow
- Token Usage Flow
- Complete User Journey

**Key Patterns:**
- Unidirectional data flow (top-down)
- Hook-based state management
- Service layer abstraction
- Optimistic UI updates
- Auto-save functionality
- Progress tracking
- Error boundaries

**Files Referenced:** 25+ component/hook/service files

---

**Last Updated:** 2025-10-07 00:08:29  
**Companion Docs:** FUNCTION_LAYER_MAPPING.md, REFACTORING_ROADMAP.md
