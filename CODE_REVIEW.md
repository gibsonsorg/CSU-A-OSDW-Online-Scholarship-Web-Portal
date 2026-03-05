# Code Review — CSU-A OSDW Online Scholarship Web Portal

**Reviewer:** Senior Web Developer (AI-assisted review)  
**Date:** 2026-03-05  
**Stack:** Laravel 12, PHP 8.2, Tailwind CSS, Vite, SQLite  

---

## Executive Summary

The application is a Laravel-based scholarship web portal for CSU Aparri OSDW. It covers student registration/login, a scholarship catalogue with an apply-modal, and a basic admin dashboard to view/approve/reject applications.

Overall the project has a working prototype foundation, but it has several **critical security vulnerabilities**, some broken functionality, inconsistent conventions, and gaps in testing. The issues below are grouped by severity.

---

## 🔴 Critical Issues

### 1. Hardcoded CSRF Token in Admin View

**File:** `resources/views/admin/welcome.blade.php` — lines 6 and 104

```html
<meta name="csrf-token" content="6cDT0vvtjHScKrglqmcJNvo62s60dYhz2TGKzqBX">
...
<input type="hidden" name="_token" value="6cDT0vvtjHScKrglqmcJNvo62s60dYhz2TGKzqBX" ...>
```

A real CSRF token has been committed verbatim into source control. This token value is now public and permanently part of git history. The correct Blade syntax must be used so the token is generated dynamically per session:

```html
<meta name="csrf-token" content="{{ csrf_token() }}">
...
@csrf
```

---

### 2. No Authorization on Admin Routes — Any Authenticated User Can Approve/Reject Applications

**File:** `routes/web.php` — lines 37–65

```php
Route::get('/admin', function () { ... })->middleware(['auth', 'verified'])->name('admin.dashboard');
Route::get('/admin/applications/{id}', function($id){ ... })->middleware(['auth']);
Route::post('/admin/applications/{id}/approve', function($id){ ... })->middleware(['auth']);
Route::post('/admin/applications/{id}/reject', function($id){ ... })->middleware(['auth']);
```

The middleware only checks `auth` (logged in), **not** that the user has an admin role. Any registered student can access `/admin`, view all applications, and approve/reject them by posting directly to the API endpoints.

**Fix:** Create a gate or middleware that checks `$user->role === 0` and apply it to all admin routes.

---

### 3. No Authorization on `StudentProfileController::store` — Unauthenticated Users Can Submit Applications

**File:** `routes/web.php` — line 86

```php
Route::post('/student-profiles', [StudentProfileController::class, 'store'])->name('student-profiles.store');
```

This route has **no authentication middleware**. An anonymous user can POST directly to this endpoint, bypassing the UI entirely, and create records in the `student_profiles` table.

**Fix:** Wrap this route in `middleware(['auth', 'verified'])`.

---

### 4. Sensitive Application Data Exposed via Unauthenticated-Friendly JSON API

**File:** `routes/web.php` — lines 45–49

```php
Route::get('/admin/applications/{id}', function($id){
    $p = StudentProfile::find($id);
    if (!$p) return response()->json(['error'=>'not_found'], 404);
    return response()->json($p);
})->middleware(['auth']);
```

The entire `StudentProfile` model (including PII: name, email, contact number, home address) is serialized and returned without restricting which fields are exposed (`$hidden` is not set on the model). This should use API Resources to whitelist fields and, as noted above, be restricted to admins only.

---

### 5. Hardcoded Absolute URLs in Admin View

**File:** `resources/views/admin/welcome.blade.php` — lines 103, 115, 118, 159

```html
<form method="POST" action="http://127.0.0.1:8000/logout" ...>
<a href="http://127.0.0.1:8000/notifications" ...>
<a href="http://127.0.0.1:8000/profile" ...>
<a href="http://127.0.0.1:8000/admin/applications" ...>
```

These localhost URLs were committed alongside the hardcoded CSRF token — clear evidence that a locally generated (rendered) HTML page was pasted back into the source. These will all break in any deployed environment.

**Fix:** Use Laravel route helpers: `route('logout')`, `route('profile.edit')`, `url('/admin/applications')`.

---

## 🟠 High Severity Issues

### 6. Route Name Conflict — `/login` and `/register` Overridden in `web.php`

**File:** `routes/web.php` — lines 16–23

```php
Route::get('/login', function () { return view('auth.register'); })->name('login');
Route::get('/register', function () { return view('auth.register'); })->name('register');
```

`auth.php` (included below) already defines `login` and `register` named routes via the `RegisteredUserController` and `AuthenticatedSessionController`. These closures shadow the auth routes, meaning the `POST /login` handler from `auth.php` still fires, but `Auth::attempt()` after a redirect will use these overrides. This is confusing and fragile. The comments in the file acknowledge "both routes point to combined custom blade file" but this should be done explicitly by modifying the controllers, not by duplicating route names.

---

### 7. `StudentProfile` Model Not Linked to `User`

**File:** `app/Models/StudentProfile.php`

The `StudentProfile` model has no `user_id` foreign key and no `belongsTo(User::class)` relationship. There is therefore no enforced link between an application and the authenticated user who submitted it. The duplicate-check in `StudentProfileController::store` relies on email matching, which a student can bypass by using a different email address.

---

### 8. Migration Uses `$table->dropColumn('role')` on Wrong Table Name

**File:** `database/migrations/2026_02_07_115838_add_role_to_user.php` — lines 24–26

```php
public function down(): void
{
    Schema::table('user', function (Blueprint $table) {   // 'user' should be 'users'
        $table->dropColumn('role');
    });
}
```

The `down()` method references the table as `'user'` instead of `'users'`. Rolling back this migration will throw an error.

---

### 9. Admin View Contains an Unclosed `<div>` and Missing Layout Components

**File:** `resources/views/admin/welcome.blade.php`

The `card` div opened at line 156 is never closed — the file ends after `</script>` without closing `</div class="card">`, `</div class="grid-2">`, `</div class="content">`, or `</div class="main">`. The HTML is structurally broken, relying on the browser's error recovery.

Additionally, the `<script>` tag is placed before `</body>` but there is no `</body>` closing tag — the `</html>` tag is also missing.

---

### 10. Student Dashboard Mixes Layout Components with Raw HTML

**File:** `resources/views/student/dashboard.blade.php` — lines 18–25

```blade
<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            </div>   {{-- mismatched closing tags --}}
        </div>
    </div>
</x-app-layout>
```

The `<x-app-layout>` Blade component wraps an empty, malformed block (three closing `</div>` tags for only two opening ones). All the actual page content is placed *outside* this component and outside any layout. The page has its own `<html>/<body>` tags, making the Blade component import pointless and the HTML invalid (duplicate `<html>` elements when rendered).

---

### 11. `console.log` Left in Production JavaScript

**File:** `public/js/users.js` — lines 101, 112

```js
console.log('Set scholarship_name to:', map.name);
console.log('Set scholarship_type to:', map.type);
```

Debug `console.log` statements should be removed before deploying to production.

---

### 12. `admin.js` References a Non-Existent `closeProfile` Element

**File:** `public/js/admin.js` — lines 84–87

```js
document.getElementById('closeProfile').addEventListener('click', function(){
    document.getElementById('profileModal').style.display = 'none';
    currentAppId = null;
});
```

There is no element with `id="closeProfile"` in `admin/welcome.blade.php`. The close button uses `onclick="document.getElementById('profileModal').style.display='none';"` inline. This `addEventListener` call will throw a `TypeError` at runtime.

---

## 🟡 Medium Severity Issues

### 13. File Uploads Stored Without Authentication Path Isolation

**File:** `app/Http/Controllers/StudentProfileController.php` — lines 40–43

```php
foreach ($request->file('uploads') as $file) {
    $uploaded[] = $file->store('student_profiles', 'public');
}
```

All uploads go into a single shared `student_profiles/` folder in public storage, with random names. There is no path-per-user isolation. Files from different students can collide in name (unlikely, but possible if using custom file names), and admin can expose files from one applicant to another via the modal's `/storage/` links.

---

### 14. No File Type Validation for Uploaded Files Beyond MIME Extension

**File:** `app/Http/Controllers/StudentProfileController.php` — line 25

```php
'uploads.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
```

The `mimes` rule in Laravel validates MIME types by inspecting the file's actual content, which is good. However, the `max:5120` (5 MB) limit applies **per file**, and there is no limit on the **number of files** or **total upload size**. A student could upload hundreds of files totalling gigabytes.

**Recommendation:** Add `max:5` to `uploads` array validation, e.g.:

```php
'uploads' => 'nullable|array|max:5',
'uploads.*' => 'file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
```

---

### 15. Old Migration Uses Class-Based Style Instead of Anonymous Class

**Files:** `database/migrations/2026_03_03_000000_create_student_profiles_table.php`, `2026_03_03_000002_...`, `2026_03_03_000003_...`

Laravel 9+ uses anonymous migration classes (`return new class extends Migration`). Three of the custom migrations use the older named-class style (`class CreateStudentProfilesTable extends Migration`), which is inconsistent and may cause class-name collision warnings if migrations are re-used or copied.

---

### 16. `MustVerifyEmail` Interface Commented Out on `User` Model

**File:** `app/Models/User.php` — line 5

```php
// use Illuminate\Contracts\Auth\MustVerifyEmail;
```

The dashboard route requires `verified` middleware:

```php
Route::get('/dashboard', ...)->middleware(['auth', 'verified'])->name('dashboard');
```

But the `User` model does not implement `MustVerifyEmail`. This means the `verified` middleware will **always pass** — email verification is silently non-functional. Either the interface should be un-commented and email sending configured, or the `verified` middleware should be removed if email verification is not required.

---

### 17. Role System Uses Magic Numbers with No Enum or Constant

**Files:** `app/Http/Controllers/Auth/AuthenticatedSessionController.php` — line 31, `app/Http/Controllers/Auth/RegisteredUserController.php` — line 44

```php
// SessionController
$fallback = (isset($user->role) && ((string)$user->role) === '0') ? route('admin.dashboard') : route('dashboard');

// RegisteredUserController
'role' => '1', // default = USER
```

Role `0` = admin, `1` = user. These magic numbers are scattered through the codebase without a central definition. Use PHP 8.1 backed enums or at minimum constants in the `User` model:

```php
const ROLE_ADMIN = 0;
const ROLE_USER  = 1;
```

---

### 18. Apply Form Does Not Pre-Fill User Email from Auth Context

**File:** `resources/views/student/dashboard.blade.php` — line 537

```html
<input class="input" type="email" name="email" required>
```

The user is authenticated (the `auth` middleware protects `/dashboard`), yet the form asks them to manually type their email. This should default to `auth()->user()->email` and ideally be read-only, tying the application to the authenticated account (which also resolves issue #7 partially).

---

### 19. Academic Scholarship "VIEW & APPLY" Buttons Are Dead Links

**File:** `resources/views/student/dashboard.blade.php` — lines 64, 80, 101, 122, etc.

```html
<a href="#" class="btn-apply">VIEW & APPLY</a>
```

Academic grant cards use `href="#"` with no JavaScript click handler attached to them (unlike non-academic cards which have the `users.js` mapping). Clicking "VIEW & APPLY" on any academic grant does nothing. Academic scholarships are also absent from the apply form's `scholarship_name` dropdown.

---

### 20. Stats in Admin Dashboard Are Hardcoded

**File:** `resources/views/admin/welcome.blade.php` — lines 129–153

```html
<div class="stat-val">0</div>  <!-- Total Grants -->
<div class="stat-val">0</div>  <!-- Total Applications -->
<div class="stat-val">0</div>  <!-- Approved -->
<div class="stat-val">3</div>  <!-- Registered Users (hardcoded!) -->
```

All statistics are hardcoded. The "3 registered users" and "3 new this month" values are fabricated. These should be computed from the database using Eloquent queries passed from the route/controller.

---

## 🟢 Low Severity / Code Quality Issues

### 21. Business Logic in Route Closures — Missing Controllers

**File:** `routes/web.php` — lines 37–65

Database queries and model manipulation sit directly in route closures instead of in dedicated controller classes. For `StudentProfile` CRUD and admin workflows, dedicated `AdminController` methods would be more maintainable, testable, and aligned with Laravel conventions.

---

### 22. Inconsistent Indentation and Formatting

Multiple files have inconsistent indentation:

- `RegisteredUserController.php` — lines 41–45: user creation block is mis-indented
- `add_role_to_user.php` — inconsistent spacing in `up()` vs `down()`
- `admin/welcome.blade.php` — mixed indentation (tabs vs spaces)

A project-level `.editorconfig` is present but not all files conform to it. Running `./vendor/bin/pint` (Laravel Pint is already a dev dependency) would auto-fix most PHP formatting issues.

---

### 23. `StudentProfile` `$fillable` Declaration on a Single Line

**File:** `app/Models/StudentProfile.php` — lines 11–13

```php
protected $fillable = [
    'first_name','middle_name','last_name','sex','status','email','home_address','contact_number','course','scholarship_name','scholarship_type','uploads','application_status'
];
```

All 13 fields are on one line, making it hard to review mass-assignment exposure. Each field should be on its own line.

---

### 24. Application Form Missing Client-Side Validation Feedback

**File:** `resources/views/student/dashboard.blade.php` — apply form (lines 502–601)

The apply form uses `required` HTML attributes but provides no server-side error display inside the modal. After a validation failure the page redirects to `/dashboard?section=non` and the modal is not re-opened with the previous values. This creates a confusing UX: the form data is lost and the user has no idea what went wrong unless they notice the session flash error at the top of the closed modal.

---

### 25. No Tests for Core Application Flows

**Directory:** `tests/`

The test suite contains only the default Breeze scaffolding (`ExampleTest.php`, `Auth/` tests, `ProfileTest.php`). There are **no feature tests** for:

- Submitting a scholarship application
- Duplicate application prevention
- Admin approve/reject flows
- Role-based access control

---

### 26. `.env.example` Sets `APP_DEBUG=true`

**File:** `.env.example` — line 4

```
APP_DEBUG=true
```

The example env file ships with debug mode on. Developers who copy this verbatim to production will expose full stack traces to end users. The example should set `APP_DEBUG=false` with a comment explaining it should be `true` only in local development.

---

### 27. Font Awesome Loaded from CDN with No Integrity Hash

**File:** `resources/views/welcome.blade.php` — line 7

```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
```

Third-party CDN resources loaded without a Subresource Integrity (SRI) `integrity` attribute are a supply-chain risk. Either add an `integrity` hash or bundle Font Awesome through `npm` and Vite.

---

## Summary Table

| # | Severity | Area | Issue |
|---|----------|------|-------|
| 1 | 🔴 Critical | Security | Hardcoded CSRF token committed to source |
| 2 | 🔴 Critical | Authorization | Admin routes lack role-based access control |
| 3 | 🔴 Critical | Authorization | Application submission route has no auth middleware |
| 4 | 🔴 Critical | Security/Privacy | Full PII model exposed via JSON API |
| 5 | 🔴 Critical | Functionality | Hardcoded localhost URLs in admin view |
| 6 | 🟠 High | Routing | Named route conflicts between `web.php` and `auth.php` |
| 7 | 🟠 High | Data Integrity | `StudentProfile` not linked to authenticated `User` |
| 8 | 🟠 High | Migrations | `down()` migration references wrong table name `'user'` |
| 9 | 🟠 High | HTML | Admin view has unclosed `<div>` tags and missing `</body>`/`</html>` |
| 10 | 🟠 High | HTML | Student dashboard mixes `<x-app-layout>` with raw HTML incorrectly |
| 11 | 🟠 High | Code Quality | `console.log` debug statements in production JS |
| 12 | 🟠 High | Functionality | `admin.js` references non-existent `closeProfile` element |
| 13 | 🟡 Medium | Security | Uploads stored in shared public folder without path isolation |
| 14 | 🟡 Medium | Security | No limit on number of uploaded files |
| 15 | 🟡 Medium | Conventions | Old-style named migration classes (inconsistent) |
| 16 | 🟡 Medium | Functionality | `MustVerifyEmail` commented out; `verified` middleware non-functional |
| 17 | 🟡 Medium | Code Quality | Role magic numbers with no constants or enum |
| 18 | 🟡 Medium | UX | Apply form does not pre-fill authenticated user's email |
| 19 | 🟡 Medium | Functionality | Academic grant "VIEW & APPLY" buttons are dead links |
| 20 | 🟡 Medium | Functionality | Admin dashboard statistics are all hardcoded |
| 21 | 🟢 Low | Architecture | Business logic in route closures instead of controllers |
| 22 | 🟢 Low | Code Quality | Inconsistent indentation/formatting across files |
| 23 | 🟢 Low | Code Quality | `$fillable` all on one line in `StudentProfile` |
| 24 | 🟢 Low | UX | No server-side error display inside apply modal after failure |
| 25 | 🟢 Low | Testing | No tests for core scholarship application flows |
| 26 | 🟢 Low | Security | `APP_DEBUG=true` in `.env.example` |
| 27 | 🟢 Low | Security | CDN resource loaded without SRI integrity hash |

---

## Recommended Priority Order

1. **Fix hardcoded CSRF token and localhost URLs** (issues #1, #5) — rotate the session/CSRF tokens as well since the old one is now public.
2. **Add role-based authorization to admin routes** (issue #2).
3. **Add `auth` middleware to the student profile submission route** (issue #3).
4. **Link `StudentProfile` to `User` via `user_id`** (issue #7) — this also enables proper ownership checks.
5. **Fix the migration `down()` typo** (issue #8).
6. **Resolve the HTML structural issues** in admin and student dashboard views (issues #9, #10).
7. **Remove `console.log` from production JS and fix the missing `closeProfile` element** (issues #11, #12).
8. Address remaining medium and low issues incrementally.
