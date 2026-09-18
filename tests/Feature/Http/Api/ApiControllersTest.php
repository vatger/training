<?php

use App\Domain\Gdpr\Events\UserDeleted;
use App\Integrations\VatEud\FakeVatEudClient;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Models\ActivityLog;
use App\Models\ApiKey;
use App\Models\Course;
use App\Models\Cpt;
use App\Models\Familiarisation;
use App\Models\FamiliarisationSector;
use App\Models\TrainingLog;
use App\Models\User;
use App\Models\WaitingListEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(VatEudClientInterface::class, FakeVatEudClient::class);
    Cache::flush();
    Http::fake(['*' => Http::response([], 200)]);
    Event::fakeExcept([
        'eloquent.creating: App\Models\ApiKey',
        'eloquent.saving: App\Models\Cpt',
        UserDeleted::class,
    ]);
});

// ─── Helpers ──────────────────────────────────────────────────────────────────

function apiCreateKey(array $permissions = [], string $plainKey = 'test-api-key'): ApiKey
{
    return ApiKey::create([
        'name' => 'Test Key',
        'key' => $plainKey,
        'is_active' => true,
        'permissions' => $permissions,
    ]);
}

function apiAuthHeaders(string $plainKey = 'test-api-key'): array
{
    return ['Authorization' => "Bearer {$plainKey}"];
}

// ─── Auth middleware shared behaviours ────────────────────────────────────────

describe('API authentication', function () {
    test('returns 401 with message when no bearer token is provided', function () {
        $this->getJson('/api/user-data/1234567')
            ->assertStatus(401)
            ->assertJson(['error' => 'No API key provided']);
    });

    test('returns 401 when bearer token does not match any key', function () {
        $this->withHeaders(['Authorization' => 'Bearer unknown-key'])
            ->getJson('/api/user-data/1234567')
            ->assertStatus(401)
            ->assertJson(['error' => 'Invalid API key']);
    });

    test('returns 401 when api key is inactive', function () {
        ApiKey::create([
            'name' => 'Inactive',
            'key' => 'inactive-key',
            'is_active' => false,
            'permissions' => ['users.read'],
        ]);

        $this->withHeaders(['Authorization' => 'Bearer inactive-key'])
            ->getJson('/api/user-data/1234567')
            ->assertStatus(401)
            ->assertJson(['error' => 'API key is inactive or expired']);
    });

    test('returns 401 when api key is expired', function () {
        ApiKey::create([
            'name' => 'Expired',
            'key' => 'expired-key',
            'is_active' => true,
            'expires_at' => now()->subDay(),
            'permissions' => ['users.read'],
        ]);

        $this->withHeaders(['Authorization' => 'Bearer expired-key'])
            ->getJson('/api/user-data/1234567')
            ->assertStatus(401)
            ->assertJson(['error' => 'API key is inactive or expired']);
    });

    test('records usage timestamp and ip on a successful request', function () {
        apiCreateKey(['users.read']);

        $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/user-data/9999999');

        $key = ApiKey::where('key', hash('sha256', 'test-api-key'))->first();
        expect($key->last_used_at)->not->toBeNull();
    });
});

// ─── UserController ──────────────────────────────────────────────────────────

describe('UserController', function () {
    test('returns 403 when api key lacks users.read permission', function () {
        apiCreateKey([], 'no-perm-key');

        $this->withHeaders(apiAuthHeaders('no-perm-key'))
            ->getJson('/api/user-data/1234567')
            ->assertStatus(403)
            ->assertJson(['error' => 'Insufficient permissions']);
    });

    test('returns user attributes when the user exists', function () {
        apiCreateKey(['users.read']);
        $user = User::factory()->create(['vatsim_id' => 1234567]);

        $response = $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/user-data/1234567');

        $response->assertOk()
            ->assertJsonFragment(['vatsim_id' => 1234567])
            ->assertJsonFragment(['email' => $user->email]);
    });

    test('returns error json with 200 status when vatsim id is not found', function () {
        apiCreateKey(['users.read']);

        $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/user-data/9999999')
            ->assertOk()
            ->assertJson(['error' => 'User not found']);
    });

    test('includes all raw user attributes in response including vatsim id and email', function () {
        apiCreateKey(['users.read']);
        $user = User::factory()->create(['vatsim_id' => 1234567, 'subdivision' => 'GER']);

        $response = $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/user-data/1234567');

        $response->assertOk()
            ->assertJsonFragment(['vatsim_id' => 1234567])
            ->assertJsonFragment(['subdivision' => 'GER'])
            ->assertJsonFragment(['email' => $user->email]);
    });

    test('never exposes the password hash or remember token', function () {
        apiCreateKey(['users.read']);
        User::factory()->create(['vatsim_id' => 1234568]);

        $response = $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/user-data/1234568');

        $response->assertOk();
        expect($response->json())->not->toHaveKeys(['password', 'remember_token']);
    });
});

// ─── CptController ────────────────────────────────────────────────────────────

describe('CptController', function () {
    test('returns 401 when no api key is provided', function () {
        $this->getJson('/api/cpts')
            ->assertStatus(401);
    });

    test('returns 403 when api key lacks cpts.read permission', function () {
        apiCreateKey([], 'no-perm-key');

        $this->withHeaders(apiAuthHeaders('no-perm-key'))
            ->getJson('/api/cpts')
            ->assertStatus(403);
    });

    test('returns empty data array when no upcoming cpts exist', function () {
        apiCreateKey(['cpts.read']);

        $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/cpts')
            ->assertOk()
            ->assertJson(['data' => []]);
    });

    test('returns upcoming pending cpts with correct structure and values', function () {
        apiCreateKey(['cpts.read']);

        $trainee = User::factory()->create(['vatsim_id' => 1111111]);
        $examiner = User::factory()->create(['vatsim_id' => 2222222]);
        $local = User::factory()->create(['vatsim_id' => 3333333]);
        $course = Course::factory()->create(['name' => 'EDDF TWR', 'solo_station' => 'EDDF_TWR']);

        Cpt::create([
            'trainee_id' => $trainee->id,
            'examiner_id' => $examiner->id,
            'local_id' => $local->id,
            'course_id' => $course->id,
            'date' => now()->addDays(5),
            'passed' => null,
        ]);

        $response = $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/cpts');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'trainee_vatsim_id', 'trainee_name', 'examiner_vatsim_id', 'examiner_name', 'local_vatsim_id', 'local_name', 'course_name', 'position', 'date', 'confirmed']]])
            ->assertJsonFragment([
                'trainee_vatsim_id' => 1111111,
                'examiner_vatsim_id' => 2222222,
                'local_vatsim_id' => 3333333,
                'course_name' => 'EDDF TWR',
                'position' => 'EDDF_TWR',
                'confirmed' => true,
            ]);
    });

    test('excludes cpts with a date in the past', function () {
        apiCreateKey(['cpts.read']);

        $trainee = User::factory()->create();
        $course = Course::factory()->create(['solo_station' => 'EDDF_TWR']);

        Cpt::create([
            'trainee_id' => $trainee->id,
            'course_id' => $course->id,
            'date' => now()->subDay(),
            'passed' => null,
        ]);

        $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/cpts')
            ->assertOk()
            ->assertJson(['data' => []]);
    });

    test('excludes cpts where passed is not null', function () {
        apiCreateKey(['cpts.read']);

        $trainee = User::factory()->create();
        $course = Course::factory()->create(['solo_station' => 'EDDF_TWR']);

        Cpt::create([
            'trainee_id' => $trainee->id,
            'course_id' => $course->id,
            'date' => now()->addDays(3),
            'passed' => true,
        ]);

        $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/cpts')
            ->assertOk()
            ->assertJson(['data' => []]);
    });

    test('orders upcoming cpts by date ascending', function () {
        apiCreateKey(['cpts.read']);

        $trainee = User::factory()->create();
        $course = Course::factory()->create(['solo_station' => 'EDDF_TWR']);

        Cpt::create(['trainee_id' => $trainee->id, 'course_id' => $course->id, 'date' => now()->addDays(10), 'passed' => null]);
        Cpt::create(['trainee_id' => $trainee->id, 'course_id' => $course->id, 'date' => now()->addDays(2), 'passed' => null]);
        Cpt::create(['trainee_id' => $trainee->id, 'course_id' => $course->id, 'date' => now()->addDays(6), 'passed' => null]);

        $response = $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/cpts');

        $dates = collect($response->json('data'))->pluck('date')->toArray();
        $sorted = $dates;
        sort($sorted);

        expect($dates)->toEqual($sorted);
    });

    test('cpt without examiner and local has confirmed false', function () {
        apiCreateKey(['cpts.read']);

        $trainee = User::factory()->create();
        $course = Course::factory()->create(['solo_station' => 'EDDF_APP']);

        Cpt::create([
            'trainee_id' => $trainee->id,
            'examiner_id' => null,
            'local_id' => null,
            'course_id' => $course->id,
            'date' => now()->addDays(3),
            'passed' => null,
        ]);

        $response = $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/cpts');

        $response->assertOk();
        expect($response->json('data.0.confirmed'))->toBeFalse();
        expect($response->json('data.0.examiner_vatsim_id'))->toBeNull();
        expect($response->json('data.0.local_vatsim_id'))->toBeNull();
    });
});

// ─── SoloController ──────────────────────────────────────────────────────────

describe('SoloController', function () {
    test('returns 401 when no api key is provided', function () {
        $this->getJson('/api/solos')
            ->assertStatus(401);
    });

    test('returns 403 when api key lacks solos.read permission', function () {
        apiCreateKey([], 'no-perm-key');

        $this->withHeaders(apiAuthHeaders('no-perm-key'))
            ->getJson('/api/solos')
            ->assertStatus(403);
    });

    test('returns solo endorsements wrapped in data key', function () {
        apiCreateKey(['solos.read']);

        // FakeVatEudClient::getSoloEndorsements() returns []
        $response = $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/solos');

        $response->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJson(['data' => []]);
    });

});

// ─── Tier1Controller ─────────────────────────────────────────────────────────

describe('Tier1Controller', function () {
    test('returns 401 when no api key is provided', function () {
        $this->getJson('/api/tier1')
            ->assertStatus(401);
    });

    test('returns 403 when api key lacks tier1.read permission', function () {
        apiCreateKey([], 'no-perm-key');

        $this->withHeaders(apiAuthHeaders('no-perm-key'))
            ->getJson('/api/tier1')
            ->assertStatus(403);
    });

    test('returns tier1 endorsements wrapped in data key', function () {
        apiCreateKey(['tier1.read']);

        // FakeVatEudClient returns one tier1 endorsement for user 1601613 at EDDL_TWR
        $response = $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/tier1');

        $response->assertOk()
            ->assertJsonStructure(['data' => [[
                'id', 'userCid', 'position', 'facility', 'createdAt',
            ]]])
            ->assertJsonFragment([
                'userCid' => 1601613,
                'position' => 'EDDL_TWR',
                'facility' => 9,
            ]);
    });

    test('returns data key with empty array when no endorsements exist', function () {
        // Override the fake client to return nothing for this one test
        $this->app->bind(VatEudClientInterface::class, function () {
            return new class extends FakeVatEudClient
            {
                public function getTier1Endorsements(): array
                {
                    return [];
                }
            };
        });

        apiCreateKey(['tier1.read']);

        $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/tier1')
            ->assertOk()
            ->assertJson(['data' => []]);
    });
});

// ─── FamiliarisationController ───────────────────────────────────────────────

describe('FamiliarisationController', function () {
    test('returns 401 when no api key is provided', function () {
        $this->getJson('/api/familiarisations')
            ->assertStatus(401);
    });

    test('returns 403 when api key lacks familiarisations.read permission', function () {
        apiCreateKey([], 'no-perm-key');

        $this->withHeaders(apiAuthHeaders('no-perm-key'))
            ->getJson('/api/familiarisations')
            ->assertStatus(403);
    });

    test('returns empty data array when no familiarisations exist', function () {
        apiCreateKey(['familiarisations.read']);

        $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/familiarisations')
            ->assertOk()
            ->assertJson(['data' => []]);
    });

    test('returns familiarisation data with vatsim id, sector name, and fir', function () {
        apiCreateKey(['familiarisations.read']);

        $user = User::factory()->create(['vatsim_id' => 5555555]);
        $sector = FamiliarisationSector::create(['name' => 'EDGG North', 'fir' => 'EDGG']);
        Familiarisation::create(['user_id' => $user->id, 'familiarisation_sector_id' => $sector->id]);

        $response = $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/familiarisations');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['vatsim_id', 'sector', 'fir']]])
            ->assertJsonFragment([
                'vatsim_id' => 5555555,
                'sector' => 'EDGG North',
                'fir' => 'EDGG',
            ]);
    });

    test('returns all familiarisations across multiple users and sectors', function () {
        apiCreateKey(['familiarisations.read']);

        $userA = User::factory()->create(['vatsim_id' => 6666666]);
        $userB = User::factory()->create(['vatsim_id' => 7777777]);
        $sectorA = FamiliarisationSector::create(['name' => 'EDGG North', 'fir' => 'EDGG']);
        $sectorB = FamiliarisationSector::create(['name' => 'EDMM South', 'fir' => 'EDMM']);

        Familiarisation::create(['user_id' => $userA->id, 'familiarisation_sector_id' => $sectorA->id]);
        Familiarisation::create(['user_id' => $userB->id, 'familiarisation_sector_id' => $sectorB->id]);

        $response = $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/familiarisations');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(2);
        $response->assertJsonFragment(['vatsim_id' => 6666666, 'fir' => 'EDGG'])
            ->assertJsonFragment(['vatsim_id' => 7777777, 'fir' => 'EDMM']);
    });
});

// ─── GdprController ──────────────────────────────────────────────────────────

describe('GdprController', function () {
    test('returns 401 when no api key is provided', function () {
        $this->deleteJson('/api/gdpr-removal/1234567')
            ->assertStatus(401);
    });

    test('returns 403 when api key lacks gdpr.delete permission', function () {
        apiCreateKey(['users.read'], 'wrong-perm-key');

        $this->withHeaders(apiAuthHeaders('wrong-perm-key'))
            ->deleteJson('/api/gdpr-removal/1234567')
            ->assertStatus(403)
            ->assertJson(['error' => 'Insufficient permissions']);
    });

    test('returns 403 when api key has no permissions', function () {
        apiCreateKey([], 'no-perm-key');

        $this->withHeaders(apiAuthHeaders('no-perm-key'))
            ->deleteJson('/api/gdpr-removal/1234567')
            ->assertStatus(403);
    });

    test('returns 200 with user not found message when vatsim id does not exist', function () {
        apiCreateKey(['gdpr.delete']);

        $this->withHeaders(apiAuthHeaders())
            ->deleteJson('/api/gdpr-removal/9999999')
            ->assertOk()
            ->assertJson(['error' => 'User not found']);
    });

    test('deletes user, fires UserDeleted event, writes activity log, and returns success', function () {
        apiCreateKey(['gdpr.delete']);
        $user = User::factory()->create(['vatsim_id' => 7654321]);

        $this->withHeaders(apiAuthHeaders())
            ->deleteJson('/api/gdpr-removal/'.$user->vatsim_id)
            ->assertOk()
            ->assertJson(['message' => 'User deleted successfully']);

        expect(User::where('vatsim_id', 7654321)->exists())->toBeFalse();

        $log = ActivityLog::where('action', 'gdpr.deletion')->first();
        expect($log)->not->toBeNull();
        expect($log->properties['vatsim_id'])->toBe(7654321);
        expect($log->properties['user_name'])->toBe($user->name);
    });

    test('anonymizes the user in place instead of deleting the row', function () {
        apiCreateKey(['gdpr.delete']);
        $user = User::factory()->create(['vatsim_id' => 7654322, 'email' => 'someone@example.com']);

        $this->withHeaders(apiAuthHeaders())
            ->deleteJson('/api/gdpr-removal/7654322')
            ->assertOk();

        $user->refresh();

        expect($user->vatsim_id)->toBeNull()
            ->and($user->first_name)->toBe('Deleted')
            ->and($user->last_name)->toBe('User')
            ->and($user->name)->toBe('Deleted User')
            ->and($user->email)->toBeNull()
            ->and($user->is_admin)->toBeFalse()
            ->and($user->is_staff)->toBeFalse()
            ->and($user->is_superuser)->toBeFalse()
            ->and($user->gdpr_deleted_at)->not->toBeNull();
    });

    test('preserves related records and shows them as Deleted User', function () {
        apiCreateKey(['gdpr.delete']);
        $user = User::factory()->create(['vatsim_id' => 7654323]);
        $course = Course::factory()->create();

        WaitingListEntry::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
        ]);

        $trainingLog = TrainingLog::create([
            'trainee_id' => $user->id,
            'session_date' => now(),
            'position' => 'EDDF_TWR',
            'type' => 'O',
            'theory' => 3,
            'phraseology' => 3,
            'coordination' => 3,
            'tag_management' => 3,
            'situational_awareness' => 3,
            'problem_recognition' => 3,
            'traffic_planning' => 3,
            'reaction' => 3,
            'separation' => 3,
            'efficiency' => 3,
            'ability_to_work_under_pressure' => 3,
            'motivation' => 3,
            'result' => true,
        ]);

        $this->withHeaders(apiAuthHeaders())
            ->deleteJson('/api/gdpr-removal/7654323')
            ->assertOk();

        expect(WaitingListEntry::where('user_id', $user->id)->exists())->toBeTrue();
        expect(TrainingLog::where('id', $trainingLog->id)->exists())->toBeTrue();

        $trainingLog->refresh();
        expect($trainingLog->trainee->name)->toBe('Deleted User');
    });

    test('deletes the visitor from VatEUD when the user is not GER subdivision', function () {
        config(['services.vateud.token' => 'fake-token']);
        apiCreateKey(['gdpr.delete']);
        $user = User::factory()->create(['vatsim_id' => 7654324, 'subdivision' => 'USA']);

        $this->withHeaders(apiAuthHeaders())
            ->deleteJson('/api/gdpr-removal/7654324')
            ->assertOk();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'facility/visitors/7654324/delete'));
    });

    test('does not call VatEUD visitor deletion for a GER subdivision user', function () {
        config(['services.vateud.token' => 'fake-token']);
        apiCreateKey(['gdpr.delete']);
        User::factory()->create(['vatsim_id' => 7654325, 'subdivision' => 'GER']);

        $this->withHeaders(apiAuthHeaders())
            ->deleteJson('/api/gdpr-removal/7654325')
            ->assertOk();

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'facility/visitors'));
    });
});
