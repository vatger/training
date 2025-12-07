<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-admin {--email=} {--password=} {--name=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admin account for development/emergency access';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creating admin account...');

        // Get input from options or prompt
        $email = $this->option('email') ?: $this->ask('Admin email address');
        $name = $this->option('name') ?: $this->ask('Admin name');
        $password = $this->option('password') ?: $this->secret('Admin password');

        // Validate input
        $validator = Validator::make([
            'email' => $email,
            'name' => $name,
            'password' => $password,
        ], [
            'email' => 'required|email|unique:users,email',
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed:');
            foreach ($validator->errors()->all() as $error) {
                $this->error('- ' . $error);
            }
            return 1;
        }

        // Check if admin already exists
        if (User::where('email', $email)->exists()) {
            $this->error('User with this email already exists!');
            return 1;
        }

        // Create admin user
        $fakeVatsimId = 9000000 + rand(100000, 999999); // Generate 7-digit ID starting with 9

        // Ensure it's unique in the database
        while (User::where('vatsim_id', $fakeVatsimId)->exists()) {
            $fakeVatsimId = 9000000 + rand(100000, 999999);
        }

        $admin = User::create([
            'vatsim_id' => $fakeVatsimId, // Fake VATSIM ID for admin accounts
            'email' => $email,
            'first_name' => explode(' ', $name)[0],
            'last_name' => implode(' ', array_slice(explode(' ', $name), 1)) ?: 'Admin',
            'password' => Hash::make($password),
            'is_admin' => true,
            'is_staff' => true,
            'is_superuser' => true,
            'email_verified_at' => now(),
            'rating' => 0, // Default rating for admin accounts
        ]);

        $this->info('Admin account created successfully!');
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $admin->id],
                ['Email', $admin->email],
                ['Name', $admin->name],
                ['Admin', 'Yes'],
                ['Created', $admin->created_at],
            ]
        );

        $this->warn('⚠️  Remember to keep these credentials secure!');
        $this->info('Admin login URL: ' . url('/admin/login'));

        return 0;
    }
}