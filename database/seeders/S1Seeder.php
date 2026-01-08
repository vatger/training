<?php

namespace Database\Seeders;

use App\Models\S1\S1Module;
use App\Models\User;
use Illuminate\Database\Seeder;

class S1Seeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'name' => 'S1 Module 1',
                'sequence_order' => 1,
                'description' => 'Basic ATC procedures and phraseology',
                'moodle_course_ids' => [],
                'moodle_quiz_ids' => [],
                'is_active' => true,
            ],
            [
                'name' => 'S1 Module 2',
                'sequence_order' => 2,
                'description' => 'Clearance delivery and ground procedures',
                'moodle_course_ids' => [86],
                'moodle_quiz_ids' => [1526, 1527, 1525, 1528],
                'is_active' => true,
            ],
            [
                'name' => 'S1 Module 3',
                'sequence_order' => 3,
                'description' => 'Tower procedures and runway operations',
                'moodle_course_ids' => [],
                'moodle_quiz_ids' => [],
                'is_active' => true,
            ],
            [
                'name' => 'S1 Module 4',
                'sequence_order' => 4,
                'description' => 'Advanced tower operations and emergencies',
                'moodle_course_ids' => [],
                'moodle_quiz_ids' => [],
                'is_active' => true,
            ],
        ];

        foreach ($modules as $module) {
            S1Module::create($module);
        }

        $this->command->info('Created ' . count($modules) . ' S1 modules');
    }
}