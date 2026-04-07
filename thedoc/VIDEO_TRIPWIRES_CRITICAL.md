# 🕵️ VIDEO GENERATION - 11 CRITICAL TRIPWIRES
**The Hidden Bugs That Will Break Video Generation**

---

## 🔴 CRITICAL TRIPWIRES

### #1: Storage Buckets Don't Exist
**Problem:** Code uploads to `webinar-videos` bucket that was NEVER created  
**Impact:** Videos generate but URLs return 404  
**Fix:** Create migration for storage buckets + RLS policies  
**Test:** Verify buckets exist before first upload

### #2: OpenAI TTS 4096 Character Limit  
**Problem:** Long scripts (10k+ chars) exceed TTS limit, code doesn't chunk  
**Impact:** Audio generation fails for webinars > 30 minutes  
**Fix:** Implement chunking + audio concatenation  
**Test:** Try 60-minute webinar (15k char script)

### #3: Blink Mock Returns Empty Objects
**Problem:** If Blink fails to init, mock has `ai: {}`, calls return undefined  
**Impact:** Cryptic "Cannot read property" errors, hard to debug  
**Fix:** Make mock throw clear errors instead of returning undefined  
**Test:** Disconnect internet during app load

### #4: Slide Image URLs Expire
**Problem:** Temp URLs expire (5min) before video assembly finishes  
**Impact:** 403 errors during assembly, video fails at 80% complete  
**Fix:** Upload to permanent storage immediately  
**Test:** Generate video with 50 slides (slow process)

### #5: Progress Bar Lies (Hides Retries)
**Problem:** Code refuses backwards progress, freezes bar during retries  
**Impact:** Users think app crashed when it's actually retrying  
**Fix:** Track per-stage progress, allow retries to reset stage  
**Test:** Trigger retry during generation (disconnect wifi briefly)

### #6: Concurrent Generations Corrupt Files
**Problem:** No lock mechanism, multiple clicks cause race conditions  
**Impact:** Videos overwrite each other, corrupted output  
**Fix:** Add generation lock per user+project  
**Test:** Rapidly click "Generate" 3 times

### #7: Memory Leak from Uncancelled Operations
**Problem:** User navigates away, generation continues in background  
**Impact:** Memory leak, wasted API calls, state update warnings  
**Fix:** Wire AbortController to component unmount  
**Test:** Start generation, navigate away at 30%

### #8: Video Exceeds 50MB Upload Limit
**Problem:** Free tier limit is 50MB, 1080p videos are 80-150MB  
**Impact:** Video generates but upload fails, looks like generation failed  
**Fix:** Check size before upload, compress if needed  
**Test:** Generate 60-minute 1080p webinar

### #9: Timeout Shorter Than Generation Time
**Problem:** 10-minute timeout, but 90-minute webinars take 15 minutes  
**Impact:** Times out at 65% progress, wastes user's time  
**Fix:** Calculate dynamic timeout based on webinar size  
**Test:** Generate 90-minute webinar with 50 slides

### #10: Race Condition in Progress Updates
**Problem:** Parallel slide processing causes out-of-order progress  
**Impact:** Progress jumps around or freezes, confuses users  
**Fix:** Queue progress updates, process sequentially  
**Test:** Generate with 30+ slides (triggers parallel processing)

### #11: No Rate Limit Handling
**Problem:** Blink API 429 errors treated as generic failures  
**Impact:** User can't generate videos for 60s, keeps retrying, fails  
**Fix:** Detect 429, wait with exponential backoff  
**Test:** Generate 5 videos rapidly (hit rate limit)

---

## ✅ PRE-LAUNCH TESTING CHECKLIST

### Critical Tests (MUST PASS):
- [ ] 10-minute webinar, 10 slides → Success
- [ ] 60-minute webinar, 30 slides → Success  
- [ ] 90-minute webinar, 50 slides → Success
- [ ] Long script (15k chars) → Chunks properly
- [ ] Click generate 3x rapidly → Only 1 runs
- [ ] Navigate away at 50% → Cancels cleanly
- [ ] Disconnect wifi during generation → Retry works
- [ ] Generate 5 videos rapidly → Rate limit handled

### Device Tests (MUST WORK):
- [ ] Desktop Chrome → Success
- [ ] Mobile Chrome → Success  
- [ ] iPhone Safari → Success
- [ ] Android Chrome → Success

### Edge Cases (SHOULD HANDLE):
- [ ] Empty script → Clear error
- [ ] No slides → Clear error
- [ ] Storage full → Clear error  
- [ ] API key invalid → Clear error
- [ ] Network drops mid-generation → Recovers

---

## 🚀 FIX PRIORITY

**Week 1 (Critical):**
1. Create storage buckets
2. Fix TTS chunking  
3. Fix Blink mock errors
4. Add generation lock

**Week 2 (High):**
5. Permanent slide storage
6. Dynamic timeout
7. Abort on unmount
8. Size check before upload

**Week 3 (Medium):**
9. Progress queue
10. Rate limit retry
11. Better error messages

---

**All 11 tripwires documented. Fix these BEFORE launch or 60%+ users will fail.**
