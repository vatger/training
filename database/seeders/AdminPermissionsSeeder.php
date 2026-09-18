<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class AdminPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'admin.access', 'description' => 'Access admin panel', 'group' => 'admin'],
            ['name' => 'admin.chief_of_trainings.view', 'description' => 'View chiefs of training in admin', 'group' => 'admin'],
            ['name' => 'admin.chief_of_trainings.edit', 'description' => 'Edit chiefs of training in admin', 'group' => 'admin'],
            ['name' => 'admin.leading_mentors.view', 'description' => 'View leading mentors in admin', 'group' => 'admin'],
            ['name' => 'admin.leading_mentors.edit', 'description' => 'Edit leading mentors in admin', 'group' => 'admin'],
            ['name' => 'admin.activity_logs.view', 'description' => 'View activity logs in admin', 'group' => 'admin'],
            ['name' => 'admin.api_keys.view', 'description' => 'View api keys in admin', 'group' => 'admin'],
            ['name' => 'admin.api_keys.edit', 'description' => 'Edit api keys in admin', 'group' => 'admin'],
            ['name' => 'admin.courses.view', 'description' => 'View courses in admin', 'group' => 'admin'],
            ['name' => 'admin.courses.edit', 'description' => 'Edit courses in admin', 'group' => 'admin'],
            ['name' => 'admin.training_logs.view', 'description' => 'View training logs in admin', 'group' => 'admin'],
            ['name' => 'admin.training_logs.edit', 'description' => 'Edit training logs in admin', 'group' => 'admin'],
            ['name' => 'admin.waiting_list_entries.view', 'description' => 'View waiting list entries in admin', 'group' => 'admin'],
            ['name' => 'admin.waiting_list_entries.edit', 'description' => 'Edit waiting list entries in admin', 'group' => 'admin'],
            ['name' => 'admin.cpts.view', 'description' => 'View CPTs in admin', 'group' => 'admin'],
            ['name' => 'admin.cpts.edit', 'description' => 'Edit CPTs in admin', 'group' => 'admin'],
            ['name' => 'admin.cpt_logs.view', 'description' => 'View CPT Logs in admin', 'group' => 'admin'],
            ['name' => 'admin.cpt_logs.edit', 'description' => 'Edit CPT Logs in admin', 'group' => 'admin'],
            ['name' => 'admin.endorsement_activities.view', 'description' => 'View endorsement activities in admin', 'group' => 'admin'],
            ['name' => 'admin.endorsement_activities.edit', 'description' => 'Edit endorsement activities in admin', 'group' => 'admin'],
            ['name' => 'admin.tier2_endorsements.view', 'description' => 'View tier 2 endorsements in admin', 'group' => 'admin'],
            ['name' => 'admin.tier2_endorsements.edit', 'description' => 'Edit tier 2 endorsements in admin', 'group' => 'admin'],
            ['name' => 'admin.examiners.view', 'description' => 'View examiners in admin', 'group' => 'admin'],
            ['name' => 'admin.examiners.edit', 'description' => 'Edit examiners in admin', 'group' => 'admin'],
            ['name' => 'admin.familiarisations.view', 'description' => 'View familiarisations in admin', 'group' => 'admin'],
            ['name' => 'admin.familiarisations.edit', 'description' => 'Edit familiarisations in admin', 'group' => 'admin'],
            ['name' => 'admin.familiarisation_sectors.view', 'description' => 'View familiarisations sectors in admin', 'group' => 'admin'],
            ['name' => 'admin.familiarisation_sectors.edit', 'description' => 'Edit familiarisations sectors in admin', 'group' => 'admin'],
            ['name' => 'admin.users.view', 'description' => 'View users in admin', 'group' => 'admin'],
            ['name' => 'admin.users.edit', 'description' => 'Edit users in admin', 'group' => 'admin'],
            ['name' => 'admin.roles.view', 'description' => 'View roles in admin', 'group' => 'admin'],
            ['name' => 'admin.roles.edit', 'description' => 'Edit roles in admin', 'group' => 'admin'],
            ['name' => 'admin.permissions.view', 'description' => 'View permissions in admin', 'group' => 'admin'],
            ['name' => 'admin.permissions.edit', 'description' => 'Edit permissions in admin', 'group' => 'admin'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }
    }
}
