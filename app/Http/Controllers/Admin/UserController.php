<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\BusinessRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Users', [
            'users' => User::orderBy('role')->orderBy('name')->get()
                ->map(fn (User $u) => [
                    ...$u->only(['id', 'name', 'username', 'email', 'is_active', 'last_login_at']),
                    'role' => $u->role->value,
                    'has_pin' => $u->hasPin(),
                ]),
            'roles' => array_map(fn (Role $r) => ['value' => $r->value, 'label' => $r->label()], Role::cases()),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());
        $this->logger->log('create', $user, null, $user->only(['name', 'username', 'role']), "Tambah user {$user->username}");

        return back()->with('success', "User {$user->username} ditambahkan.");
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        unset($data['password']);
        $this->guardOwnerAccounts($request, $user, $data);

        $original = $user->getAttributes();
        $user->update($data);
        $this->logger->logChanges('update', $user, $original, "Ubah user {$user->username}");

        return back()->with('success', "User {$user->username} diperbarui.");
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', 'string', Password::min(8)]]);
        $user->update(['password' => $data['password']]);
        $this->logger->log('reset_password', $user, null, null, "Reset password {$user->username}");

        return back()->with('success', "Password {$user->username} direset.");
    }

    /** Owner minimal 1 aktif, dan owner tidak bisa menonaktifkan/menurunkan dirinya sendiri. */
    private function guardOwnerAccounts(Request $request, User $user, array $data): void
    {
        $deactivating = array_key_exists('is_active', $data) && ! $data['is_active'];
        $demoting = $user->isOwner() && $data['role'] !== Role::Owner->value;

        if ($user->is($request->user())) {
            BusinessRule::check(! $deactivating && ! $demoting, 'Anda tidak bisa menonaktifkan atau menurunkan role akun sendiri.', 'is_active');
        }

        if ($user->isOwner() && ($deactivating || $demoting)) {
            $otherOwners = User::owners()->active()->whereKeyNot($user->id)->count();
            BusinessRule::check($otherOwners > 0, 'Minimal harus ada 1 owner aktif.', 'is_active');
        }
    }
}
