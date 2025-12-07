<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'EDGG Mentor',
                'description' => 'FIR Langen Mentor'
            ],
            [
                'name' => 'EDMM Mentor', 
                'description' => 'FIR München Mentor'
            ],
            [
                'name' => 'EDWW Mentor',
                'description' => 'FIR EDWW Mentor'
            ],
            [
                'name' => 'ATD Leitung',
                'description' => 'Chief ATD'
            ],
            [
                'name' => 'VATGER Leitung',
                'description' => 'VATGER Director'
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                ['description' => $role['description']]
            );
        }
    }
}