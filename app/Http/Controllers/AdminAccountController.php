<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\Admin\AdminAccountRequest;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAccountController extends Controller
{
    public function index(Request $request): View
    {
        $accounts = User::query()
            ->whereIn('role', [Role::Admin, Role::SuperAdmin])
            ->withCount('activityLogs')
            ->when($request->filled('q'), fn (Builder $query) => $query->where(function (Builder $query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where('name', 'like', $term)->orWhere('email', 'like', $term);
            }))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('is_active', $request->input('status') === 'active'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.accounts', compact('accounts'));
    }

    public function store(AdminAccountRequest $request, ActivityLogService $logs): RedirectResponse
    {
        $account = User::query()->create([
            ...$request->safe()->except(['password_confirmation']),
            'is_active' => $request->boolean('is_active', true),
        ]);
        $logs->log($request->user(), 'admin_account_created', $account, ['role' => $account->role->value]);

        return back()->with('status', 'Administrator account created.');
    }

    public function update(AdminAccountRequest $request, User $account, ActivityLogService $logs): RedirectResponse
    {
        $this->assertAdminAccount($account);
        $this->assertSafeStateChange($request, $account);

        $data = $request->safe()->except(['password', 'password_confirmation']);
        $data['is_active'] = $request->boolean('is_active');
        if ($request->filled('password')) {
            $data['password'] = $request->string('password')->toString();
        }
        $before = $account->only(['name', 'email', 'phone', 'role', 'is_active']);
        $account->update($data);
        $logs->log($request->user(), 'admin_account_updated', $account, [
            'before' => $before,
            'after' => $account->only(['name', 'email', 'phone', 'role', 'is_active']),
        ]);

        return back()->with('status', 'Administrator account updated.');
    }

    public function toggle(Request $request, User $account, ActivityLogService $logs): RedirectResponse
    {
        $this->assertAdminAccount($account);
        abort_if($request->user()->is($account), 422, 'You cannot deactivate your own account.');
        if ($account->isSuperAdmin() && $account->is_active) {
            $this->assertAnotherActiveSuperAdmin($account);
        }
        $account->update(['is_active' => ! $account->is_active]);
        $logs->log($request->user(), $account->is_active ? 'admin_account_activated' : 'admin_account_deactivated', $account);

        return back()->with('status', 'Administrator status updated.');
    }

    public function resetPassword(ResetPasswordRequest $request, User $account, ActivityLogService $logs): RedirectResponse
    {
        $this->assertAdminAccount($account);
        $account->update(['password' => $request->string('password')->toString()]);
        $logs->log($request->user(), 'admin_password_reset', $account);

        return back()->with('status', 'Administrator password reset.');
    }

    public function destroy(Request $request, User $account, ActivityLogService $logs): RedirectResponse
    {
        $this->assertAdminAccount($account);
        abort_if($request->user()->is($account), 422, 'You cannot delete your own account.');
        if ($account->isSuperAdmin()) {
            $this->assertAnotherActiveSuperAdmin($account);
        }
        $logs->log($request->user(), 'admin_account_deleted', $account, ['email' => $account->email, 'role' => $account->role->value]);
        $account->delete();

        return back()->with('status', 'Administrator account deleted.');
    }

    public function activity(User $account): View
    {
        $this->assertAdminAccount($account);

        return view('admin.account-activity', [
            'account' => $account,
            'logs' => $account->activityLogs()->latest()->paginate(25),
        ]);
    }

    private function assertAdminAccount(User $account): void
    {
        abort_unless(in_array($account->role, [Role::Admin, Role::SuperAdmin], true), 404);
    }

    private function assertSafeStateChange(AdminAccountRequest $request, User $account): void
    {
        if ($request->user()->is($account)) {
            abort_if(! $request->boolean('is_active') || $request->input('role') !== Role::SuperAdmin->value, 422, 'You cannot deactivate or demote your own Super Admin account.');
        }
        if ($account->isSuperAdmin() && ($request->input('role') !== Role::SuperAdmin->value || ! $request->boolean('is_active'))) {
            $this->assertAnotherActiveSuperAdmin($account);
        }
    }

    private function assertAnotherActiveSuperAdmin(User $account): void
    {
        abort_unless(User::query()
            ->where('role', Role::SuperAdmin)
            ->where('is_active', true)
            ->whereKeyNot($account->id)
            ->exists(), 422, 'The last active Super Admin cannot be removed or deactivated.');
    }
}
