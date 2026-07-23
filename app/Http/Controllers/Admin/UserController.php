<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::query()->orderBy('name')->get(['id', 'name', 'email', 'role']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Form', ['mode' => 'create']);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => Hash::make($data['password']),
        ]);

        ActivityLog::record('created', 'users', $user->name);

        return redirect()->route('admin.users.index')->with('status', "Pengguna \"{$user->name}\" berhasil dibuat.");
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'mode' => 'edit',
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if ($user->isSuperadmin() && $data['role'] !== 'superadmin' && $this->isLastSuperadmin($user)) {
            return back()->withErrors(['role' => 'Tidak bisa mengubah role — ini superadmin terakhir.']);
        }

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        ActivityLog::record('updated', 'users', $user->name);

        return redirect()->route('admin.users.index')->with('status', "Pengguna \"{$user->name}\" berhasil diperbarui.");
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus akun Anda sendiri.');
        }

        if ($user->isSuperadmin() && $this->isLastSuperadmin($user)) {
            return back()->with('error', 'Tidak bisa menghapus superadmin terakhir.');
        }

        $label = $user->name;
        $user->delete();

        ActivityLog::record('deleted', 'users', $label);

        return redirect()->route('admin.users.index')->with('status', "Pengguna \"{$label}\" berhasil dihapus.");
    }

    private function isLastSuperadmin(User $user): bool
    {
        return User::where('role', 'superadmin')->where('id', '!=', $user->id)->doesntExist();
    }
}
