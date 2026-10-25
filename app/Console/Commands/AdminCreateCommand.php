<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * CLI-only admin account creation — no public signup route, matching
 * the original app's scripts/create-admin.js. Running this again with
 * the same email updates (upserts) the password, useful for a reset
 * without touching the database directly.
 */
class AdminCreateCommand extends Command
{
    protected $signature = 'admin:create {email} {password?} {--superadmin : Create a new superadmin without promoting an existing editor}';

    protected $description = 'Create an administrator or reset an existing account password';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->argument('password') ?? $this->secret('Password (minimal 8 karakter)', false);

        $validator = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => ['required', 'email'], 'password' => ['required', 'string', 'min:8']]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::firstOrNew(['email' => $email]);

        if ($this->option('superadmin') && $user->exists && ! $user->isSuperadmin()) {
            $this->error('Existing editors cannot be promoted by this command. Use Pengguna as a superadmin.');

            return self::FAILURE;
        }

        if (! $user->exists) {
            $user->role = $this->option('superadmin') ? 'superadmin' : 'editor';
        }

        $user->fill(['name' => 'Admin', 'password' => Hash::make($password)])->save();

        $this->info("Admin user ready: {$user->email}");

        return self::SUCCESS;
    }
}
