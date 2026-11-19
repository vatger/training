<?php

use App\Data\AuthUserData;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('guests receive a null user and default shared props', function () {
    $this->get(route('login'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.user', null)
            ->where('theme', 'system')
            ->where('sidebarOpen', true)
            ->where('flash.success', null)
            ->where('flash.error', null)
        );
});

test('authenticated users receive the shared user shape', function () {
    $user = User::factory()->create(['subdivision' => 'GER', 'rating' => 3]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.id', $user->id)
            ->where('auth.user.vatsim_id', $user->vatsim_id)
            ->where('auth.user.name', $user->name)
            ->where('auth.user.is_vatsim_user', true)
            ->where('auth.user.roles', [])
            ->where('auth.user.is_mentor', false)
            ->where('auth.user.is_leadership', false)
            ->where('auth.user.is_leading_mentor', false)
            ->missing('auth.user.password')
        );
});

test('mentor role is reflected in the shared user', function () {
    $user = User::factory()->create(['subdivision' => 'GER', 'rating' => 3]);
    $user->roles()->attach(Role::create(['name' => 'EDGG Mentor']));

    expect(AuthUserData::fromUser($user->fresh()))
        ->roles->toBe(['EDGG Mentor'])
        ->is_mentor->toBeTrue();
});

test('the theme comes from the user settings', function () {
    $user = User::factory()->create(['subdivision' => 'GER', 'rating' => 3]);
    UserSetting::create(['user_id' => $user->id, 'theme' => 'dark']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('theme', 'dark'));
});

test('the sidebar state is read from the cookie', function () {
    $user = User::factory()->create(['subdivision' => 'GER', 'rating' => 3]);

    $this->actingAs($user)
        ->withUnencryptedCookie('sidebar_state', 'false')
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page->where('sidebarOpen', false));
});
