<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $user = Auth::user();

        // Pending accounts must complete email verification first.
        if ($user && $user->account_status === 'pending') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect(route('login'))->with('error', 'Please verify your email before logging in.');
        }

        // Check if user account was rejected
        if ($user && $user->account_status === 'rejected') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect(route('login'))->with('error', 'Your account application was rejected. Reason: ' . ($user->rejection_reason ?? 'Not specified'));
        }

        $isAdmin = isset($user->role) && (string) $user->role === '0';
        $fallback = $isAdmin
            ? route('admin.dashboard', absolute: false)
            : route('dashboard', absolute: false);

        $intendedUrl = $request->session()->pull('url.intended');
        $intendedPath = is_string($intendedUrl) ? parse_url($intendedUrl, PHP_URL_PATH) : null;
        $intendedHost = is_string($intendedUrl) ? parse_url($intendedUrl, PHP_URL_HOST) : null;
        $intendedQuery = is_string($intendedUrl) ? parse_url($intendedUrl, PHP_URL_QUERY) : null;
        $intendedPathIsSafe = is_string($intendedPath)
            && str_starts_with($intendedPath, '/')
            && !str_starts_with($intendedPath, '//')
            && !str_contains($intendedPath, '\\');
        $intendedIsLocal = $intendedHost === null
            ? $intendedPathIsSafe
            : is_string($intendedHost) && strcasecmp($intendedHost, $request->getHost()) === 0;
        $intendedIsAdminRoute = is_string($intendedPath)
            && (
                $intendedPath === '/admin'
                || str_starts_with($intendedPath, '/admin/')
                || $intendedPath === '/api/backup'
                || str_starts_with($intendedPath, '/api/backup/')
            );

        if ($intendedIsLocal && $intendedIsAdminRoute === $isAdmin) {
            return redirect($intendedPath . (is_string($intendedQuery) ? '?' . $intendedQuery : ''));
        }

        return redirect($fallback);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $isAdmin = (string) Auth::user()?->role === '0';

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login', $isAdmin ? ['login_type' => 'admin'] : []);
    }
}
