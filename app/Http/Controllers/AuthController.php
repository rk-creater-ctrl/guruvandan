<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showAdminLogin(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login', ['portal' => 'admin']);
    }

    public function showStudentLogin(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login', ['portal' => 'student']);
    }

    public function showTeacherLogin(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login', ['portal' => 'teacher']);
    }

    public function login(LoginRequest $request, ActivityLogService $logs, string $portal = 'admin'): RedirectResponse
    {
        $roleMap = [
            'student' => [Role::Student->value],
            'teacher' => [Role::Teacher->value],
            'admin' => [Role::Admin->value, Role::SuperAdmin->value],
        ];
        abort_unless(isset($roleMap[$portal]), 404);

        $credentials = $request->validated();
        $identifier = $portal === 'admin'
            ? strtolower((string) $credentials['email'])
            : strtolower((string) $credentials['username']);

        $user = User::query()
            ->whereIn('role', $roleMap[$portal])
            ->where('is_active', true)
            ->when($portal === 'admin', fn ($query) => $query->whereRaw('lower(email) = ?', [$identifier]))
            ->when($portal !== 'admin', fn ($query) => $query->whereRaw('lower(username) = ?', [$identifier]))
            ->first();

        if (! $user || ! Auth::attempt(['id' => $user->id, 'password' => $credentials['password'], 'is_active' => true], $request->boolean('remember'))) {
            $logs->log(null, 'login_failed', null, ['portal' => $portal, 'identifier_hash' => hash('sha256', $identifier)]);

            return back()->withErrors([$portal === 'admin' ? 'email' : 'username' => 'Invalid credentials or inactive account.'])->onlyInput($portal === 'admin' ? 'email' : 'username');
        }

        $request->session()->regenerate();
        $request->user()->update(['last_login_at' => now()]);
        $logs->log($request->user(), 'login');

        if ($request->user()->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function adminLogin(LoginRequest $request, ActivityLogService $logs): RedirectResponse
    {
        return $this->login($request, $logs, 'admin');
    }

    public function studentLogin(LoginRequest $request, ActivityLogService $logs): RedirectResponse
    {
        return $this->login($request, $logs, 'student');
    }

    public function teacherLogin(LoginRequest $request, ActivityLogService $logs): RedirectResponse
    {
        return $this->login($request, $logs, 'teacher');
    }

    public function redirectDashboard(): RedirectResponse
    {
        return redirect()->route(match (auth()->user()?->role->value) {
            'student' => 'student.dashboard',
            'teacher' => 'teacher.dashboard',
            default => 'admin.dashboard',
        });
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request, ActivityLogService $logs): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::query()->create([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'username' => $request->string('username')->toString(),
                'phone' => $request->input('phone'),
                'role' => Role::Student,
                'must_change_password' => false,
                'password' => $request->string('password')->toString(),
            ]);

            $user->studentProfile()->create([
                'class_name' => $request->string('class_name')->toString(),
                'section' => $request->input('section'),
            ]);

            return $user;
        });

        Auth::login($user);
        $logs->log($user, 'student_registered', $user);

        return redirect()->route('student.dashboard');
    }

    public function showChangePassword(): View
    {
        return view('auth.change-password');
    }

    public function changePassword(ChangePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => $request->string('password')->toString(),
            'must_change_password' => false,
        ]);

        return redirect()->route('dashboard')->with('status', 'Password updated. Welcome to GuruVandan.');
    }

    public function logout(): RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    }
}
