<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ChangeUserPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:change-password';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change user password';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $uniqueRow = $this->ask('For which user do you want to change password? (write user id or username):');

        $user = User::where('username', $uniqueRow)
            ->orWhere('id', (int) $uniqueRow)
            ->first();
        if (! $user) {
            $this->error('User not found');

            return self::FAILURE;
        }

        $password = $this->ask("Type in new password for {$user->username} and hit enter:");
        if (! strlen($password) >= 3) {
            $this->error('Password must be at least 3 characters long.');

            return self::INVALID;
        }

        $user->update(['password' => bcrypt($password)]);

        return self::SUCCESS;
    }
}
