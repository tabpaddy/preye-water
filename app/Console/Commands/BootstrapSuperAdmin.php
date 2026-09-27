<?php

namespace App\Console\Commands;

use App\Services\StaffService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;

class BootstrapSuperAdmin extends Command
{
    protected $signature = 'staff:bootstrap';

    protected $description = 'Interactively create the first Super Admin';

    public function handle(StaffService $service): int
    {
        $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);
        $data = ['first_name' => $this->ask('First name'), 'last_name' => $this->ask('Last name'), 'email' => $this->ask('Email (optional)'), 'password' => $this->secret('Password (at least 12 characters)'), 'password_confirmation' => $this->secret('Confirm password')];
        $staff = $service->bootstrap($data);
        $this->info('Super Admin created. Sign in at /staff using '.$staff->staff_number);

        return self::SUCCESS;
    }
}
