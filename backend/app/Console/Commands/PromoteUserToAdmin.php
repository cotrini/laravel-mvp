<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('users:promote-admin {email}')]
#[Description('Promote an existing user account to administrator')]
class PromoteUserToAdmin extends Command
{
    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error('No user account exists with that email address.');

            return self::FAILURE;
        }

        $user->role = User::ROLE_ADMIN;
        $user->save();

        $this->info("{$user->email} is now an administrator.");

        return self::SUCCESS;
    }
}
