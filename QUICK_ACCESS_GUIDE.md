# Quick Access Guide - ACSEE Candidates Tab

## 🎯 Access the Feature

### Direct URL
```
http://localhost:8001/exam-types/acsee
```

### Via Navigation
```
1. Dashboard
2. Click ACSEE (or Exam Types → ACSEE)
3. Click "Candidates" tab
```

---

## ✨ What You See

A **read-only table** showing ACSEE candidates with:
- Index Number
- Full Name
- Sex (♂ Male / ♀ Female)
- Combination (e.g., PCM)
- **Allocated Subjects** (e.g., PHY, CHE, MAT)
- School Name

---

## 🔧 What You Can Do

✅ **View**: See all ACSEE candidates  
✅ **Search**: By index number or name  
✅ **Paginate**: Navigate through pages (15 per page)  
✅ **Export**: Download to CSV/Excel  
✅ **See Subjects**: View subjects from combination  

❌ **Cannot**: Register, edit, or delete (do that in `/registration/candidates`)

---

## 📋 Files Modified

```
resources/views/exam-types/acsee.blade.php  (Candidates tab HTML + Alpine.js)
app/Http/Controllers/ExamTypeController.php (getAcseeCandicates() method)
routes/api.php                              (GET /api/exam-types/{code}/candidates)
```

---

## 🚀 Test Now

```
1. Go to: http://localhost:8001/exam-types/acsee
2. Click: "Candidates" tab
3. Verify: Candidates display
4. Try: Search, pagination, export
```

Done! ✅
