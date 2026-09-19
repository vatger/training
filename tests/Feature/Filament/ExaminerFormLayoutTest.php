<?php

use App\Filament\Resources\Examiners\ExaminerResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_superuser' => true]));
});

test('examiner create page renders with the full-width positions checkbox list', function () {
    $this->get(ExaminerResource::getUrl('create'))->assertSuccessful();
});
