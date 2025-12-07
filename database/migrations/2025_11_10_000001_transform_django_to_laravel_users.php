<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('auth_user')) {
            return;
        }

        $hasUserDetail = Schema::hasTable('connect_userdetail');

        if ($hasUserDetail) {
            DB::statement("
                INSERT INTO users (
                    id, vatsim_id, first_name, last_name, email, email_verified_at,
                    subdivision, rating, last_rating_change, is_staff, is_superuser, is_admin,
                    password, remember_token, created_at, updated_at
                )
                SELECT 
                    id,
                    username as vatsim_id,
                    first_name,
                    last_name,
                    CASE 
                        WHEN email IS NULL OR email = '' THEN CONCAT('noemail_', id, '@placeholder.vatger.de')
                        ELSE email
                    END as email,
                    NULL as email_verified_at,
                    (SELECT subdivision FROM connect_userdetail WHERE user_id = auth_user.id LIMIT 1) as subdivision,
                    COALESCE((SELECT rating FROM connect_userdetail WHERE user_id = auth_user.id LIMIT 1), 1) as rating,
                    (SELECT last_rating_change FROM connect_userdetail WHERE user_id = auth_user.id LIMIT 1) as last_rating_change,
                    is_staff,
                    is_superuser,
                    is_superuser as is_admin,
                    password,
                    NULL as remember_token,
                    date_joined as created_at,
                    date_joined as updated_at
                FROM auth_user
                WHERE NOT EXISTS (SELECT 1 FROM users WHERE users.id = auth_user.id)
            ");
        } else {
            $subdivisionCol = Schema::hasColumn('auth_user', 'subdivision') ? 'subdivision' : 'NULL';
            $ratingCol = Schema::hasColumn('auth_user', 'rating') ? 'rating' : '1';
            $lastRatingChangeCol = Schema::hasColumn('auth_user', 'last_rating_change') ? 'last_rating_change' : 'NULL';
            
            DB::statement("
                INSERT INTO users (
                    id, vatsim_id, first_name, last_name, email, email_verified_at,
                    subdivision, rating, last_rating_change, is_staff, is_superuser, is_admin,
                    password, remember_token, created_at, updated_at
                )
                SELECT 
                    id,
                    username as vatsim_id,
                    first_name,
                    last_name,
                    CASE 
                        WHEN email IS NULL OR email = '' THEN CONCAT('noemail_', id, '@placeholder.vatger.de')
                        ELSE email
                    END as email,
                    NULL as email_verified_at,
                    {$subdivisionCol} as subdivision,
                    {$ratingCol} as rating,
                    {$lastRatingChangeCol} as last_rating_change,
                    is_staff,
                    is_superuser,
                    is_superuser as is_admin,
                    password,
                    NULL as remember_token,
                    date_joined as created_at,
                    date_joined as updated_at
                FROM auth_user
                WHERE NOT EXISTS (SELECT 1 FROM users WHERE users.id = auth_user.id)
            ");
        }
    }

    public function down(): void
    {
    }
};