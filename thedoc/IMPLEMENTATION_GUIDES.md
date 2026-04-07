# 🔧 IMPLEMENTATION GUIDES
**Step-by-Step Code Examples for Critical Tasks**

---

## 1. ERROR MONITORING (Day 1) ⚡

### Install Sentry
```bash
npm install @sentry/react @sentry/tracing
```

### Create SentryService.ts
```typescript
// src/services/monitoring/SentryService.ts
import * as Sentry from '@sentry/react'

export function initializeSentry() {
  if (import.meta.env.VITE_SENTRY_DSN) {
    Sentry.init({
      dsn: import.meta.env.VITE_SENTRY_DSN,
      environment: import.meta.env.MODE,
      tracesSampleRate: 1.0,
      replaysSessionSampleRate: 0.1,
      replaysOnErrorSampleRate: 1.0,
    })
  }
}
```

### Update main.tsx
```typescript
import { initializeSentry } from './services/monitoring/SentryService'

initializeSentry() // Before React render

ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
)
```

---

## 2. LOGGER SERVICE (Day 2) ⚡

### Create LoggerService.ts
```typescript
// src/services/LoggerService.ts
import * as Sentry from '@sentry/react'

class LoggerService {
  private isDev = import.meta.env.MODE === 'development'

  debug(message: string, data?: any) {
    if (this.isDev) console.log(`[DEBUG] ${message}`, data || '')
  }

  info(message: string, data?: any) {
    console.info(`[INFO] ${message}`, data || '')
  }

  warn(message: string, data?: any) {
    console.warn(`[WARN] ${message}`, data || '')
    Sentry.captureMessage(message, { level: 'warning', extra: data })
  }

  error(message: string, error?: Error, data?: any) {
    console.error(`[ERROR] ${message}`, error || '', data || '')
    if (error) Sentry.captureException(error, { extra: { message, ...data } })
  }
}

export const logger = new LoggerService()
```

### Replace console.log
```bash
# Find all console.log statements
find src -name "*.ts" -o -name "*.tsx" | xargs grep "console\."

# Replace manually or with sed:
# console.log → logger.debug
# console.error → logger.error
# console.warn → logger.warn
```

---

## 3. RATE LIMITING (Day 3) ⚡

### Create RateLimitService.ts
```typescript
// src/services/RateLimitService.ts
interface RateLimit {
  count: number
  resetAt: number
}

export class RateLimitService {
  private limits = new Map<string, RateLimit>()
  
  async checkLimit(
    userId: string,
    action: string,
    maxRequests: number = 10,
    windowMs: number = 60000
  ): Promise<{ allowed: boolean; remaining: number; resetIn: number }> {
    const key = `${userId}:${action}`
    const now = Date.now()
    const limit = this.limits.get(key)

    if (!limit || now > limit.resetAt) {
      this.limits.set(key, { count: 1, resetAt: now + windowMs })
      return { allowed: true, remaining: maxRequests - 1, resetIn: windowMs }
    }

    if (limit.count >= maxRequests) {
      return { allowed: false, remaining: 0, resetIn: limit.resetAt - now }
    }

    limit.count++
    return { allowed: true, remaining: maxRequests - limit.count, resetIn: limit.resetAt - now }
  }
}

export const rateLimiter = new RateLimitService()
```

### Apply to AIService
```typescript
// src/services/aiService.ts
import { rateLimiter } from './RateLimitService'

async generateOutline(userId: string, topic: string, ...args) {
  const { allowed, remaining, resetIn } = await rateLimiter.checkLimit(
    userId, 
    'generate_outline', 
    5, // max 5 per minute
    60000
  )
  
  if (!allowed) {
    throw new Error(`Rate limit exceeded. Try again in ${Math.ceil(resetIn/1000)}s`)
  }

  // Continue with generation...
}
```

---

## 4. DATABASE BACKUPS (Day 4) ⚡

### Enable PITR
1. Go to Supabase Dashboard
2. Database → Backups
3. Enable Point-in-Time Recovery
4. Cost: $10/month

### Add Soft Delete
```sql
-- supabase/migrations/003_soft_delete.sql
ALTER TABLE webinar_projects ADD COLUMN deleted_at TIMESTAMPTZ;

CREATE INDEX idx_projects_deleted ON webinar_projects(deleted_at) 
WHERE deleted_at IS NOT NULL;

-- Update RLS to exclude deleted
DROP POLICY "Users can view own projects" ON webinar_projects;
CREATE POLICY "Users can view own projects" ON webinar_projects
  FOR SELECT USING (auth.uid() = user_id AND deleted_at IS NULL);
```

### Update Delete Function
```typescript
// src/hooks/useWebinarProject.ts
const deleteProject = async (projectId: string) => {
  // Soft delete
  await db.webinarProjects.update(projectId, {
    deleted_at: new Date().toISOString()
  })
  
  setProjects(prev => prev.filter(p => p.id !== projectId))
}
```

---

## 5. SERVER VIDEO PROCESSING (Day 5-7) ⚡⚡⚡

### Create Edge Function
```bash
supabase functions new generate-video
```

### Edge Function Code
```typescript
// supabase/functions/generate-video/index.ts
import { serve } from 'https://deno.land/std@0.168.0/http/server.ts'
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'

serve(async (req) => {
  const { userId, projectId, audioUrl, slideUrls, quality } = await req.json()
  
  const supabase = createClient(
    Deno.env.get('SUPABASE_URL')!,
    Deno.env.get('SUPABASE_SERVICE_ROLE_KEY')!
  )

  try {
    // Update progress
    await supabase
      .from('webinar_projects')
      .update({ status: 'generating', generation_progress: 10 })
      .eq('id', projectId)

    // Process video (use Replicate, AWS Lambda, or similar)
    const videoUrl = await processVideo({ audioUrl, slideUrls, quality })

    // Complete
    await supabase
      .from('webinar_projects')
      .update({ 
        status: 'completed', 
        generation_progress: 100,
        video_url: videoUrl 
      })
      .eq('id', projectId)

    return new Response(JSON.stringify({ videoUrl }), {
      headers: { 'Content-Type': 'application/json' }
    })
  } catch (error) {
    await supabase
      .from('webinar_projects')
      .update({ status: 'failed' })
      .eq('id', projectId)
      
    throw error
  }
})
```

### Client Service
```typescript
// src/services/ServerVideoService.ts
import { supabase } from '../lib/supabase'
import { logger } from './LoggerService'

export class ServerVideoService {
  async generateVideo(
    projectId: string,
    userId: string,
    audioUrl: string,
    slideUrls: string[],
    onProgress?: (progress: number) => void
  ) {
    // Call Edge Function
    const { data, error } = await supabase.functions.invoke('generate-video', {
      body: { projectId, userId, audioUrl, slideUrls, quality: '1080p' }
    })

    if (error) throw error

    // Poll for progress
    const pollProgress = setInterval(async () => {
      const { data: project } = await supabase
        .from('webinar_projects')
        .select('generation_progress, status')
        .eq('id', projectId)
        .single()

      if (project) {
        onProgress?.(project.generation_progress)
        
        if (project.status === 'completed') {
          clearInterval(pollProgress)
        }
      }
    }, 2000)

    return data.videoUrl
  }
}
```

### Remove FFmpeg.wasm
```bash
# Uninstall client-side FFmpeg
npm uninstall @ffmpeg/ffmpeg @ffmpeg/util

# Delete old files
rm src/utils/videoGenerator.ts
rm src/utils/actualVideoGenerator.ts
rm src/utils/realVideoGenerator.ts
rm src/utils/enhancedVideoGenerator.ts

# Remove from public folder
rm public/ffmpeg-core.js
rm public/ffmpeg-worker.js
```

---

## 6. PERFORMANCE OPTIMIZATION (Week 2) ⚡

### Remove Unused Dependencies
```bash
npm uninstall @react-three/drei @react-three/fiber  # -300 KB
```

### Add Lazy Loading
```typescript
// src/App.tsx
import { lazy, Suspense } from 'react'

const Dashboard = lazy(() => import('./components/Dashboard'))
const WebinarCreator = lazy(() => import('./components/WebinarCreator'))
const AdminPanel = lazy(() => import('./components/AdminPanel'))

function App() {
  return (
    <Suspense fallback={<LoadingSpinner />}>
      {currentView === 'dashboard' && <Dashboard />}
      {currentView === 'creator' && <WebinarCreator />}
      {currentView === 'admin' && <AdminPanel />}
    </Suspense>
  )
}
```

### Optimize Vite Config
```typescript
// vite.config.ts
export default defineConfig({
  plugins: [react()],
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          'vendor-react': ['react', 'react-dom', 'react-router-dom'],
          'vendor-ui': [
            '@radix-ui/react-dialog',
            '@radix-ui/react-select',
            '@radix-ui/react-toast'
          ],
          'vendor-utils': ['date-fns', 'dompurify', 'zod']
        }
      }
    },
    chunkSizeWarningLimit: 1000
  }
})
```

### Extract Templates to JSON
```bash
# Move templates out of JS bundle
mv src/utils/professionalTemplates.ts public/data/templates.json
mv src/utils/webinarTemplates.ts public/data/webinar-templates.json

# Load dynamically
const templates = await fetch('/data/templates.json').then(r => r.json())
```

---

## 7. CI/CD PIPELINE (Week 5) ⚡

### GitHub Actions
```yaml
# .github/workflows/deploy.yml
name: Deploy

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Install
        run: npm ci
      
      - name: Type Check
        run: npm run type-check
      
      - name: Lint
        run: npm run lint
      
      - name: Test
        run: npm run test:coverage
      
      - name: Build
        run: npm run build

  deploy:
    needs: test
    if: github.ref == 'refs/heads/main'
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Deploy to Vercel
        uses: amondnet/vercel-action@v25
        with:
          vercel-token: ${{ secrets.VERCEL_TOKEN }}
          vercel-org-id: ${{ secrets.ORG_ID }}
          vercel-project-id: ${{ secrets.PROJECT_ID }}
          vercel-args: '--prod'
```

---

## 8. MONITORING SETUP (Week 5) ⚡

### Add Analytics
```typescript
// src/services/AnalyticsService.ts
import mixpanel from 'mixpanel-browser'

class AnalyticsService {
  init() {
    if (import.meta.env.VITE_MIXPANEL_TOKEN) {
      mixpanel.init(import.meta.env.VITE_MIXPANEL_TOKEN)
    }
  }

  track(event: string, properties?: Record<string, any>) {
    mixpanel.track(event, properties)
  }

  identify(userId: string, traits?: Record<string, any>) {
    mixpanel.identify(userId)
    if (traits) mixpanel.people.set(traits)
  }
}

export const analytics = new AnalyticsService()
```

### Track Key Events
```typescript
// Track important actions
analytics.track('Webinar Created', {
  template: selectedTemplate,
  duration: durationMinutes,
  aiProvider: aiTool
})

analytics.track('Video Generated', {
  duration: videoDuration,
  quality: videoQuality
})

analytics.track('Export Completed', {
  format: exportFormat
})
```

### Web Vitals
```typescript
// src/services/performance.ts
import { getCLS, getFID, getFCP, getLCP, getTTFB } from 'web-vitals'

function sendToAnalytics(metric: any) {
  analytics.track('Web Vital', {
    name: metric.name,
    value: metric.value,
    rating: metric.rating
  })
}

getCLS(sendToAnalytics)
getFID(sendToAnalytics)
getFCP(sendToAnalytics)
getLCP(sendToAnalytics)
getTTFB(sendToAnalytics)
```

---

## 9. TESTING SETUP (Week 3) ⚡

### Install Playwright
```bash
npm install -D @playwright/test
npx playwright install
```

### Create Test Config
```typescript
// playwright.config.ts
import { defineConfig } from '@playwright/test'

export default defineConfig({
  testDir: './tests',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: 'html',
  use: {
    baseURL: 'http://localhost:3000',
    trace: 'on-first-retry',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
  webServer: {
    command: 'npm run dev',
    url: 'http://localhost:3000',
    reuseExistingServer: !process.env.CI,
  },
})
```

### Write E2E Test
```typescript
// tests/webinar-creation.spec.ts
import { test, expect } from '@playwright/test'

test('create webinar end-to-end', async ({ page }) => {
  // Login
  await page.goto('/')
  await page.click('text=Sign In')
  
  // Create webinar
  await page.click('text=Create New Webinar')
  await page.fill('input[name="topic"]', 'Test Webinar')
  await page.fill('textarea[name="description"]', 'Test description')
  await page.selectOption('select[name="duration"]', '30')
  
  // Generate outline
  await page.click('text=Generate Outline')
  await page.waitForSelector('text=Outline generated', { timeout: 30000 })
  
  // Next step
  await page.click('text=Next')
  
  // Verify we're on slide design step
  await expect(page.locator('text=Slide Design')).toBeVisible()
})
```

---

## 10. SECURITY CHECKLIST ⚡

### Content Security Policy
```html
<!-- index.html -->
<meta http-equiv="Content-Security-Policy" 
      content="
        default-src 'self';
        script-src 'self' 'unsafe-inline';
        style-src 'self' 'unsafe-inline';
        img-src 'self' https: data:;
        connect-src 'self' https://*.supabase.co;
        font-src 'self' data:;
      ">
```

### Verify DOMPurify Usage
```typescript
// Check all dangerouslySetInnerHTML uses
import DOMPurify from 'dompurify'

function SafeHTML({ html }: { html: string }) {
  return (
    <div dangerouslySetInnerHTML={{ 
      __html: DOMPurify.sanitize(html, {
        ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'ul', 'li'],
        ALLOWED_ATTR: []
      })
    }} />
  )
}
```

### Security Headers (Vercel)
```json
// vercel.json
{
  "headers": [
    {
      "source": "/(.*)",
      "headers": [
        {
          "key": "X-Content-Type-Options",
          "value": "nosniff"
        },
        {
          "key": "X-Frame-Options",
          "value": "DENY"
        },
        {
          "key": "X-XSS-Protection",
          "value": "1; mode=block"
        },
        {
          "key": "Referrer-Policy",
          "value": "strict-origin-when-cross-origin"
        }
      ]
    }
  ]
}
```

---

**These implementation guides provide copy-paste ready code for all critical tasks. Follow them in order for fastest results.**

**Next:** Start with Day 1 tasks in GO_LIVE_PLAN.md
