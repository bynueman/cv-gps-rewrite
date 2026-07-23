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
    protected $signature = 'admin:create {email} {password}';

    protected $description = 'Create or update the single admin account';

    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

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

        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => 'Admin', 'password' => Hash::make($password)]
        );

        $this->info("Admin user ready: {$user->email}");

        return self::SUCCESS;
    }
}
