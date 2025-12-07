<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('auth_group')) {
            return;
        }

        DB::statement('
            INSERT INTO roles (id, name, description, created_at, updated_at)
            SELECT 
                id,
                name,
                NULL as description,
                NOW() as created_at,
                NOW() as updated_at
            FROM auth_group
            WHERE NOT EXISTS (SELECT 1 FROM roles WHERE roles.id = auth_group.id)
        ');

        if (Schema::hasTable('auth_user_groups')) {
            DB::statement('
                INSERT INTO user_roles (user_id, role_id, created_at, updated_at)
                SELECT 
                    user_id,
                    group_id as role_id,
                    NOW() as created_at,
                    NOW() as updated_at
                FROM auth_user_groups
                WHERE NOT EXISTS (
                    SELECT 1 FROM user_roles 
                    WHERE user_roles.user_id = auth_user_groups.user_id 
                    AND user_roles.role_id = auth_user_groups.group_id
                )
            ');
        }
    }

    public function down(): void
    {
    }
};