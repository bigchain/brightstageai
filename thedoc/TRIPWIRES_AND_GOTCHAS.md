# 🕵️ TRIPWIRES & GOTCHAS - The Hidden Landmines
**What Will Break That You Think Won't**

*"The devil is in the details." - Every Great Detective*

---

## 🎯 CRITICAL TRIPWIRES DETECTED

### SEVERITY LEVELS
- 🔴 **CRITICAL** - Will cause production outage
- 🟠 **HIGH** - Will cause data loss or corruption
- 🟡 **MEDIUM** - Will cause bugs/poor UX
- 🔵 **LOW** - Will cause technical debt

---

## 🔴 TRIPWIRE #1: Memory Leaks from Timers

### **THE TRAP:**
```typescript
// src/components/WebinarCreator.tsx:250
useEffect(() => {
  autoSaveTimeout = setTimeout(() => {
    handleSave(true)
  }, 30000)
  
  return () => clearTimeout(autoSaveTimeout)  
}, [webinarData, handleSave, saveState.loading])
```

### **THE PROBLEM:**
Every time `webinarData` changes (every keystroke!), a new timer is created and the old one cleared. BUT if `handleSave` reference changes, you get:
1. Multiple concurrent auto-saves
2. Race conditions (save 1 overwrites save 2)
3. Wasted database writes
4. Memory leak over time

### **HOW IT BREAKS:**
```
User types "H" → Timer 1 starts (30s)
User types "e" → Timer 1 cleared, Timer 2 starts
User types "l" → Timer 2 cleared, Timer 3 starts
...after 30 seconds...
All timers fire simultaneously → 30 save operations
Database gets hammered → Supabase rate limit hit
```

### **THE FIX:**
```typescript
// Use debounce instead
const debouncedSave = useMemo(
  () => debounce((data) => {
    handleSave(data, true)
  }, 30000),
  [] // Empty deps - create once
)

useEffect(() => {
  if (webinarData.topic || webinarData.description) {
    debouncedSave(webinarData)
  }
  
  return () => debouncedSave.cancel()
}, [webinarData, debouncedSave])
```

### **TEST FOR THIS:**
```typescript
// In tests
it('should not create multiple save timers', () => {
  const { rerender } = render(<WebinarCreator />)
  
  act(() => {
    // Type 10 characters rapidly
    for (let i = 0; i < 10; i++) {
      fireEvent.change(input, { target: { value: `text${i}` } })
    }
  })
  
  // Fast-forward 30 seconds
  act(() => jest.advanceTimersByTime(30000))
  
  // Should only save ONCE, not 10 times
  expect(mockSave).toHaveBeenCalledTimes(1)
})
```

---

## 🔴 TRIPWIRE #2: React Dependency Hell

### **THE TRAP:**
```typescript
// src/hooks/useWebinarProject.ts:65
useEffect(() => {
  loadProjects()
}, [userId]) // eslint-disable-line react-hooks/exhaustive-deps
```

### **THE PROBLEM:**
The `eslint-disable` comment is HIDING a real problem. `loadProjects` is created with `useCallback` but depends on `userId`. If `loadProjects` changes, this effect WON'T re-run because it's not in the dependency array.

### **HOW IT BREAKS:**
```
1. User A logs in → loadProjects created for user A
2. User A logs out → Effect doesn't re-run
3. User B logs in → userId changes to B
4. Effect runs loadProjects(A) → Loads wrong user's data!
5. User B sees User A's webinars → DATA LEAK
```

### **THE FIX:**
```typescript
const loadProjects = useCallback(async () => {
  if (!userId) {
    setProjects([])
    return
  }
  
  // ... existing code
}, [userId]) // Include userId in deps

useEffect(() => {
  loadProjects()
}, [loadProjects]) // Now safe to include
```

### **TEST FOR THIS:**
```typescript
it('should load correct projects when user changes', async () => {
  const { rerender } = renderHook(
    ({ userId }) => useWebinarProject(userId),
    { initialProps: { userId: 'user1' } }
  )
  
  await waitFor(() => expect(mockLoadProjects).toHaveBeenCalledWith('user1'))
  
  // Change user
  rerender({ userId: 'user2' })
  
  await waitFor(() => expect(mockLoadProjects).toHaveBeenCalledWith('user2'))
  expect(mockLoadProjects).not.toHaveBeenCalledWith('user1')
})
```

---

## 🔴 TRIPWIRE #3: Race Conditions in AI Calls

### **THE TRAP:**
```typescript
// Multiple async operations without coordination
const outline = await generateOutline()
const slides = await generateSlides(outline)
const script = await generateScript(slides)
```

### **THE PROBLEM:**
If user clicks "Generate" twice rapidly:
```
Click 1: generateOutline() starts
Click 2: generateOutline() starts AGAIN
...
Click 1 completes → Sets outline A
Click 2 completes → Sets outline B (overwrites A)
But slides are already generating from outline A!
Result: Slides don't match outline
```

### **HOW IT BREAKS:**
```
User: "Generate slides for AI presentation"
[Clicks generate]
[Impatient, clicks again after 2 seconds]

Backend:
- Request 1: Outline about "AI in Healthcare" (random variation)
- Request 2: Outline about "AI in Finance" (random variation)

Request 2 finishes first → Sets outline
Request 1 finishes → Generates slides from different outline
Result: Slides about Healthcare, outline about Finance
```

### **THE FIX:**
```typescript
// Use abort controller
const abortControllerRef = useRef<AbortController>()

async function generateOutline() {
  // Cancel previous request
  abortControllerRef.current?.abort()
  abortControllerRef.current = new AbortController()
  
  try {
    const outline = await aiService.generateOutline({
      signal: abortControllerRef.current.signal,
      ...params
    })
    
    setOutline(outline)
  } catch (error) {
    if (error.name === 'AbortError') {
      // Expected, user started new generation
      return
    }
    throw error
  }
}
```

### **TEST FOR THIS:**
```typescript
it('should cancel previous generation when new one starts', async () => {
  const { result } = renderHook(() => useAIGeneration())
  
  // Start first generation
  const promise1 = result.current.generate('topic1')
  
  // Start second immediately
  const promise2 = result.current.generate('topic2')
  
  await promise2
  
  // First should be aborted
  await expect(promise1).rejects.toThrow('AbortError')
  
  // Only second result should be saved
  expect(result.current.outline).toMatchObject({ topic: 'topic2' })
})
```

---

## 🟠 TRIPWIRE #4: Environment Variable Hell

### **THE TRAP:**
```typescript
// Works in dev, breaks in prod
const apiKey = import.meta.env.VITE_OPENAI_API_KEY
```

### **THE PROBLEM:**
1. `.env.local` exists in dev, `.env.production` doesn't
2. Vite only exposes vars prefixed with `VITE_`
3. Build-time vs runtime values confusion
4. Vercel/production has different var names

### **HOW IT BREAKS:**
```
Development:
- .env.local has VITE_OPENAI_API_KEY=sk-dev123
- Works perfectly

Production Deploy:
- Vercel env vars set as OPENAI_API_KEY (no VITE_ prefix!)
- App builds successfully
- Runtime: import.meta.env.VITE_OPENAI_API_KEY = undefined
- All AI calls fail silently
- Users see "Error generating content" everywhere
```

### **THE FIX:**
```typescript
// src/config/env.ts
interface Config {
  supabaseUrl: string
  supabaseAnonKey: string
  stripePublishableKey: string
  sentryDsn?: string
  environment: 'development' | 'staging' | 'production'
}

function getConfig(): Config {
  const config = {
    supabaseUrl: import.meta.env.VITE_SUPABASE_URL,
    supabaseAnonKey: import.meta.env.VITE_SUPABASE_ANON_KEY,
    stripePublishableKey: import.meta.env.VITE_STRIPE_PUBLISHABLE_KEY,
    sentryDsn: import.meta.env.VITE_SENTRY_DSN,
    environment: import.meta.env.MODE as any,
  }
  
  // Validate required vars
  const required: (keyof Config)[] = ['supabaseUrl', 'supabaseAnonKey']
  const missing = required.filter(key => !config[key])
  
  if (missing.length > 0) {
    throw new Error(
      `Missing required environment variables: ${missing.join(', ')}\n` +
      `Make sure all VITE_* variables are set in your .env file`
    )
  }
  
  return config
}

export const config = getConfig()
```

### **DEPLOYMENT CHECKLIST:**
```bash
# vercel.json - Document all required vars
{
  "env": {
    "VITE_SUPABASE_URL": "@supabase-url",
    "VITE_SUPABASE_ANON_KEY": "@supabase-anon-key",
    "VITE_STRIPE_PUBLISHABLE_KEY": "@stripe-publishable-key",
    "VITE_SENTRY_DSN": "@sentry-dsn"
  }
}
```

### **TEST FOR THIS:**
```typescript
it('should fail fast if required env vars missing', () => {
  // Mock missing var
  vi.stubEnv('VITE_SUPABASE_URL', undefined)
  
  expect(() => {
    import('./config/env')
  }).toThrow('Missing required environment variables: supabaseUrl')
})
```

---

## 🟠 TRIPWIRE #5: Database ID Collision

### **THE TRAP:**
```typescript
// src/hooks/useWebinarProject.ts:74
const newProject = {
  id: `webinar_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
  // ...
}
```

### **THE PROBLEM:**
`Date.now()` has millisecond precision. If two users create projects at the EXACT same millisecond (very possible with multi-tab or multi-user):
```
User A: webinar_1704672000123_abc123
User B: webinar_1704672000123_xyz789 (different random, but...)
```

The random part is only 9 characters from base36. Collision probability with 10,000 users:
- Birthday paradox: ~1% chance of collision

### **HOW IT BREAKS:**
```
User A creates project → ID: webinar_1704672000123_abc123
User B creates project 1ms later → ID: webinar_1704672000123_abc123 (COLLISION!)

Database:
INSERT INTO projects VALUES (id='webinar_1704672000123_abc123', userId='A', ...)
INSERT INTO projects VALUES (id='webinar_1704672000123_abc123', userId='B', ...)
→ UNIQUE CONSTRAINT VIOLATION

User B sees: "Failed to create project"
Developer sees: Generic database error
No clear indication of ID collision
```

### **THE FIX:**
```typescript
// Use crypto.randomUUID() - guaranteed unique
import { v4 as uuidv4 } from 'uuid'

const newProject = {
  id: uuidv4(), // e.g., '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d'
  // ...
}

// OR use database-generated IDs
const { data, error } = await supabase
  .from('webinar_projects')
  .insert({
    // Let database generate UUID
    userId,
    title,
    ...
  })
  .select()
  .single()
```

### **TEST FOR THIS:**
```typescript
it('should create unique IDs for concurrent projects', async () => {
  const ids = await Promise.all(
    Array.from({ length: 1000 }, () => createProject())
  )
  
  const uniqueIds = new Set(ids)
  expect(uniqueIds.size).toBe(1000) // No collisions
})
```

---

## 🟡 TRIPWIRE #6: Event Listener Memory Leaks

### **THE TRAP:**
```typescript
// src/components/WebinarCreator.tsx:291
useEffect(() => {
  window.addEventListener('keydown', handleKeyDown)
  return () => window.removeEventListener('keydown', handleKeyDown)
}, [handleSave])
```

### **THE PROBLEM:**
`handleSave` is created with `useCallback` and depends on many things. Every time it changes:
1. Old event listener is removed
2. New event listener is added
3. BUT if removal fails (different function reference), you get DUPLICATE listeners

### **HOW IT BREAKS:**
```
Component renders 10 times (normal during development)
Each time handleSave reference changes
10 event listeners registered

User presses Ctrl+S:
- Handler fires 10 times
- 10 concurrent save operations
- Race conditions
- Database overwhelmed
```

### **THE FIX:**
```typescript
// Use a ref to avoid recreating listener
const handleSaveRef = useRef(handleSave)

useEffect(() => {
  handleSaveRef.current = handleSave
}, [handleSave])

useEffect(() => {
  const handleKeyDown = (e: KeyboardEvent) => {
    if (e.ctrlKey && e.key === 's') {
      e.preventDefault()
      handleSaveRef.current() // Always calls latest
    }
  }
  
  window.addEventListener('keydown', handleKeyDown)
  return () => window.removeEventListener('keydown', handleKeyDown)
}, []) // Empty deps - listener never changes
```

---

## 🟡 TRIPWIRE #7: Unhandled Promise Rejections

### **THE TRAP:**
```typescript
// src/services/aiService.ts:84
const response = await blink.ai.generateText({ ... })
```

### **THE PROBLEM:**
If user navigates away during AI generation:
1. Component unmounts
2. Promise still pending
3. Promise rejects (network error, timeout, etc.)
4. No one is listening for the rejection
5. **Unhandled promise rejection** → App crashes in production

### **HOW IT BREAKS:**
```
User clicks "Generate Outline"
→ AI call starts (30 second operation)
→ User gets impatient, clicks "Back"
→ Component unmounts
→ AI call fails (component gone)
→ Unhandled rejection
→ Error boundary catches it
→ Entire app shows error screen
```

### **THE FIX:**
```typescript
// Add cleanup tracking
const isMountedRef = useRef(true)

useEffect(() => {
  return () => {
    isMountedRef.current = false
  }
}, [])

async function generateOutline() {
  try {
    const response = await blink.ai.generateText({ ... })
    
    // Only update state if still mounted
    if (isMountedRef.current) {
      setOutline(response)
    }
  } catch (error) {
    if (isMountedRef.current) {
      setError(error)
    } else {
      // Component unmounted, ignore error
      console.debug('Operation cancelled - component unmounted')
    }
  }
}
```

---

## 🟡 TRIPWIRE #8: State Updates on Unmounted Components

### **THE TRAP:**
```typescript
async function loadProjects() {
  setLoading(true)
  const projects = await db.projects.list()
  setProjects(projects)  // Might be unmounted!
  setLoading(false)
}
```

### **THE PROBLEM:**
React Warning: "Can't perform a React state update on an unmounted component"

This warning exists because:
1. Memory leak (closure holds reference to unmounted component)
2. Wasted work (updating state no one will see)
3. Can cause subtle bugs if state update triggers other effects

### **THE FIX:**
```typescript
useEffect(() => {
  let cancelled = false
  
  async function loadProjects() {
    if (cancelled) return
    setLoading(true)
    
    const projects = await db.projects.list()
    
    if (!cancelled) {
      setProjects(projects)
      setLoading(false)
    }
  }
  
  loadProjects()
  
  return () => {
    cancelled = true
  }
}, [userId])
```

---

## 🔴 TRIPWIRE #9: Silent Failures in Error Handling

### **THE TRAP:**
```typescript
try {
  const result = await apiCall()
} catch (error) {
  console.error('Error:', error)
  // That's it - no user feedback!
}
```

### **THE PROBLEM:**
Error is logged but:
1. User sees nothing (no error message)
2. Operation appears to succeed (no indication of failure)
3. User keeps using app in broken state
4. Downstream operations fail mysteriously

### **HOW IT BREAKS:**
```
User clicks "Generate Video"
→ API call fails (rate limit, network, etc.)
→ Error logged to console
→ UI still shows "Generating..." spinner forever
→ User waits 30 minutes
→ Refreshes page
→ All work lost
→ User rage-quits
```

### **THE FIX:**
```typescript
try {
  const result = await apiCall()
  return result
} catch (error) {
  logger.error('API call failed', error, { context })
  
  // Show user-friendly error
  toast({
    title: 'Operation Failed',
    description: getUserFriendlyMessage(error),
    variant: 'destructive'
  })
  
  // Update UI state
  setStatus('failed')
  
  // Re-throw for upstream handling
  throw error
}

function getUserFriendlyMessage(error: any): string {
  if (error.message?.includes('rate limit')) {
    return 'Too many requests. Please wait a moment and try again.'
  }
  if (error.message?.includes('network')) {
    return 'Network error. Please check your connection.'
  }
  if (error.message?.includes('timeout')) {
    return 'Request timed out. Please try again.'
  }
  return 'An unexpected error occurred. Please try again.'
}
```

---

## 🔴 TRIPWIRE #10: Type Coercion Bugs

### **THE TRAP:**
```typescript
// src/components/steps/ContentInputStep.tsx
const duration = formData.get('duration') // Returns string "30"
await generateOutline(topic, duration) // Expects number 30
```

### **THE PROBLEM:**
JavaScript's type coercion can cause silent bugs:
```typescript
"30" + 10 = "3010" // String concatenation instead of addition
"30" == 30 // true (loose equality)
"30" === 30 // false (strict equality)

// In database query:
WHERE duration = "30" // Might not match duration = 30
```

### **HOW IT BREAKS:**
```
User selects "30 minutes"
→ Form value: "30" (string)
→ Saved to database: duration = "30"
→ Later query: WHERE duration > 20
→ String comparison: "30" > "20" is TRUE
→ But "5" > "20" is also TRUE! (alphabetical comparison)
→ Wrong webinars returned
```

### **THE FIX:**
```typescript
// Always parse and validate
const durationStr = formData.get('duration')
const duration = parseInt(durationStr, 10)

if (isNaN(duration) || duration <= 0) {
  throw new Error('Invalid duration')
}

// Use Zod for automatic validation
const schema = z.object({
  topic: z.string().min(1),
  duration: z.number().int().positive(),
  audience: z.string().min(1)
})

const validated = schema.parse({
  topic,
  duration: parseInt(duration, 10),
  audience
})
```

---

## 🔍 DETECTION STRATEGIES

### 1. **Automated Testing**
```typescript
// Add these to your test suite:

// Memory leak detection
it('should clean up timers on unmount', () => {
  const { unmount } = render(<Component />)
  const timerCount = jest.getTimerCount()
  unmount()
  expect(jest.getTimerCount()).toBe(0)
})

// Race condition detection
it('should handle concurrent requests', async () => {
  const promises = Array.from({ length: 10 }, () => 
    component.generate()
  )
  await Promise.all(promises)
  expect(component.state.generateCount).toBe(1) // Last one wins
})

// State update after unmount detection
it('should not update state after unmount', async () => {
  const { unmount } = render(<Component />)
  const promise = component.asyncOperation()
  unmount()
  await promise
  // Should not throw warning
})
```

### 2. **Runtime Monitoring**
```typescript
// Add to production code:

// Detect duplicate event listeners
const originalAddEventListener = window.addEventListener
const listeners = new Map()

window.addEventListener = function(type, listener, options) {
  const key = `${type}-${listener.toString()}`
  if (listeners.has(key)) {
    console.warn('Duplicate event listener detected:', type)
  }
  listeners.set(key, true)
  return originalAddEventListener.call(this, type, listener, options)
}

// Detect slow state updates
const originalSetState = React.useState
React.useState = function(...args) {
  const [state, setState] = originalSetState(...args)
  
  return [state, (...setArgs) => {
    const start = performance.now()
    setState(...setArgs)
    const duration = performance.now() - start
    
    if (duration > 16) { // Longer than 1 frame
      console.warn('Slow state update detected:', duration, 'ms')
    }
  }]
}
```

### 3. **Code Review Checklist**
```markdown
For every PR, check:

□ All useEffect have proper cleanup
□ All timers are cleared
□ All event listeners are removed
□ All async operations handle cancellation
□ All state updates check if component is mounted
□ All Promise rejections are handled
□ All user inputs are validated
□ All database queries use parameterized queries
□ All env vars are documented
□ All errors show user feedback
```

---

## 🎯 PREVENTION CHECKLIST

### Before You Code:
- [ ] Read this document thoroughly
- [ ] Understand the tripwires in your area
- [ ] Plan error handling first
- [ ] Design for cancellation

### While You Code:
- [ ] Use TypeScript strictly (no 'any')
- [ ] Add cleanup to every effect
- [ ] Validate all external inputs
- [ ] Handle all promise rejections
- [ ] Test edge cases

### Before You Deploy:
- [ ] Run all tripwire tests
- [ ] Check for memory leaks
- [ ] Verify env vars in production
- [ ] Test with slow network
- [ ] Test with concurrent users
- [ ] Test navigation during async ops

---

## 🚨 IMMEDIATE ACTIONS REQUIRED

### This Week:
1. **Fix Timer Leaks** (Day 2)
   - Replace setTimeout with debounce
   - Add cleanup to all timers

2. **Fix Dependency Arrays** (Day 3)
   - Remove all eslint-disable comments
   - Fix real dependency issues

3. **Add Abort Controllers** (Day 4)
   - Cancel previous AI calls
   - Handle navigation during operations

4. **Validate Environment** (Day 5)
   - Create config.ts with validation
   - Test production deploy

5. **Fix ID Generation** (Day 5)
   - Switch to UUID
   - Test concurrent creation

---

## 📚 RESOURCES

### Testing Tools:
- **React Testing Library** - Component testing
- **Playwright** - E2E testing
- **Jest Leak Detector** - Memory leak detection
- **why-did-you-render** - Unnecessary re-render detection

### Monitoring Tools:
- **Sentry** - Error tracking
- **LogRocket** - Session replay
- **React DevTools Profiler** - Performance profiling

### Linting Rules to Add:
```json
{
  "rules": {
    "react-hooks/exhaustive-deps": "error",
    "no-console": ["error", { "allow": ["warn", "error"] }],
    "@typescript-eslint/no-floating-promises": "error",
    "@typescript-eslint/no-misused-promises": "error"
  }
}
```

---

**Remember: Every bug you prevent is 100x easier than debugging in production.**

*"An ounce of prevention is worth a pound of cure." - Benjamin Franklin*
