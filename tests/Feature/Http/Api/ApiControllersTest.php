<?php

use App\Integrations\Moodle\MoodleClientInterface;
use App\Integrations\VatEud\FakeVatEudClient;
use App\Integrations\VatEud\VatEudClientInterface;
use App\Models\ApiKey;
use App\Models\Course;
use App\Models\Cpt;
use App\Models\Familiarisation;
use App\Models\FamiliarisationSector;
use App\Models\GdprRemovalRequest;
use App\Models\Subfamiliarisation;
use App\Models\SubfamiliarisationSession;
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
        $this->getJson('/api/gdpr-data/1234567')
            ->assertStatus(401)
            ->assertJson(['error' => 'No API key provided']);
    });

    test('returns 401 when bearer token does not match any key', function () {
        $this->withHeaders(['Authorization' => 'Bearer unknown-key'])
            ->getJson('/api/gdpr-data/1234567')
            ->assertStatus(401)
            ->assertJson(['error' => 'Invalid API key']);
    });

    test('returns 401 when api key is inactive', function () {
        ApiKey::create([
            'name' => 'Inactive',
            'key' => 'inactive-key',
            'is_active' => false,
            'permissions' => ['gdpr.read'],
        ]);

        $this->withHeaders(['Authorization' => 'Bearer inactive-key'])
            ->getJson('/api/gdpr-data/1234567')
            ->assertStatus(401)
            ->assertJson(['error' => 'API key is inactive or expired']);
    });

    test('returns 401 when api key is expired', function () {
        ApiKey::create([
            'name' => 'Expired',
            'key' => 'expired-key',
            'is_active' => true,
            'expires_at' => now()->subDay(),
            'permissions' => ['gdpr.read'],
        ]);

        $this->withHeaders(['Authorization' => 'Bearer expired-key'])
            ->getJson('/api/gdpr-data/1234567')
            ->assertStatus(401)
            ->assertJson(['error' => 'API key is inactive or expired']);
    });

    test('records usage timestamp and ip on a successful request', function () {
        apiCreateKey(['gdpr.read']);

        $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/gdpr-data/9999999');

        $key = ApiKey::where('key', hash('sha256', 'test-api-key'))->first();
        expect($key->last_used_at)->not->toBeNull();
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

    test('does not list a user holding only one subfamiliarisation sector as qualified when requirements are unmet', function () {
        apiCreateKey(['familiarisations.read']);

        $primary = FamiliarisationSector::create(['name' => 'EMS', 'fir' => 'EDMM']);
        $secondary = FamiliarisationSector::create(['name' => 'NH', 'fir' => 'EDMM']);
        $subfamiliarisation = Subfamiliarisation::create([
            'name' => 'EMS / NH Maastricht Combined Control',
            'primary_sector_id' => $primary->id,
            'secondary_sector_id' => $secondary->id,
            'moodle_course_ids' => [101, 102],
        ]);

        $user = User::factory()->create(['vatsim_id' => 8888888]);
        Familiarisation::create(['user_id' => $user->id, 'familiarisation_sector_id' => $subfamiliarisation->primary_sector_id]);

        $response = $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/familiarisations');

        $response->assertOk();
        expect($response->json('subfamiliarisation_qualifications'))->toBeEmpty();
    });

    test('lists a user who qualifies via the subfamiliarisation in subfamiliarisation_qualifications', function () {
        apiCreateKey(['familiarisations.read']);

        $primary = FamiliarisationSector::create(['name' => 'EMS', 'fir' => 'EDMM']);
        $secondary = FamiliarisationSector::create(['name' => 'NH', 'fir' => 'EDMM']);
        $subfamiliarisation = Subfamiliarisation::create([
            'name' => 'EMS / NH Maastricht Combined Control',
            'primary_sector_id' => $primary->id,
            'secondary_sector_id' => $secondary->id,
            'moodle_course_ids' => [101, 102],
        ]);

        $user = User::factory()->create(['vatsim_id' => 9999999]);
        Familiarisation::create(['user_id' => $user->id, 'familiarisation_sector_id' => $subfamiliarisation->primary_sector_id]);

        $mentor = User::factory()->create();
        SubfamiliarisationSession::create([
            'subfamiliarisation_id' => $subfamiliarisation->id,
            'user_id' => $user->id,
            'mentor_id' => $mentor->id,
            'completed_at' => now(),
        ]);

        $moodleClient = new class implements MoodleClientInterface
        {
            public function userExists(int $vatsimId): bool
            {
                return true;
            }

            public function getCourseCompletion(int $vatsimId, int $courseId): bool
            {
                return true;
            }

            public function getCourseName(int $courseId): ?string
            {
                return 'Moodle Course';
            }

            public function enrollUser(int $vatsimId, int $courseId): bool
            {
                return true;
            }
        };
        $this->app->instance(MoodleClientInterface::class, $moodleClient);

        $response = $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/familiarisations');

        $response->assertOk()
            ->assertJsonFragment([
                'vatsim_id' => 9999999,
                'subfamiliarisation' => 'EMS / NH Maastricht Combined Control',
                'granted_sector' => 'NH',
                'granted_fir' => 'EDMM',
            ]);
    });
});

// ─── GdprController ──────────────────────────────────────────────────────────

describe('GdprController removal requests', function () {
    test('returns 401 when no api key is provided', function () {
        $this->deleteJson('/api/gdpr-removal/1234567')
            ->assertStatus(401);
    });

    test('returns 403 when api key lacks gdpr.delete permission', function () {
        apiCreateKey(['gdpr.read'], 'wrong-perm-key');

        $this->withHeaders(apiAuthHeaders('wrong-perm-key'))
            ->deleteJson('/api/gdpr-removal/1234567')
            ->assertStatus(403)
            ->assertJson(['error' => 'Insufficient permissions']);
    });

    test('returns 200 when there is no user and no request for the vatsim id', function () {
        apiCreateKey(['gdpr.delete']);

        $this->withHeaders(apiAuthHeaders())
            ->deleteJson('/api/gdpr-removal/9999999')
            ->assertOk();

        expect(GdprRemovalRequest::count())->toBe(0);
    });

    test('the first request creates a pending removal request and is answered with 202', function () {
        apiCreateKey(['gdpr.delete']);
        $user = User::factory()->create(['vatsim_id' => 7654321, 'first_name' => 'Max', 'last_name' => 'Muster']);

        $this->withHeaders(apiAuthHeaders())
            ->deleteJson('/api/gdpr-removal/7654321')
            ->assertStatus(202)
            ->assertJson(['status' => 'pending']);

        $request = GdprRemovalRequest::sole();
        expect($request->vatsim_id)->toBe(7654321)
            ->and($request->user_id)->toBe($user->id)
            ->and($request->user_name)->toBe('Max Muster')
            ->and($request->isPending())->toBeTrue()
            ->and($request->search_terms)->toContain('Max Muster', '7654321');
    });

    test('nothing is deleted by the request', function () {
        apiCreateKey(['gdpr.delete']);
        $user = User::factory()->create(['vatsim_id' => 7654322]);

        $this->withHeaders(apiAuthHeaders())->deleteJson('/api/gdpr-removal/7654322')->assertStatus(202);

        expect($user->fresh())->not->toBeNull();
    });

    test('further requests for the same user are answered the same way without creating more requests', function () {
        apiCreateKey(['gdpr.delete']);
        User::factory()->create(['vatsim_id' => 7654323]);

        foreach (range(1, 3) as $_) {
            $this->withHeaders(apiAuthHeaders())
                ->deleteJson('/api/gdpr-removal/7654323')
                ->assertStatus(202);
        }

        expect(GdprRemovalRequest::count())->toBe(1);
    });

    test('is answered with 202 while an approved request still has records to review', function () {
        apiCreateKey(['gdpr.delete']);
        GdprRemovalRequest::create(['user_id' => null, 'vatsim_id' => 7654324, 'status' => 'approved']);

        $this->withHeaders(apiAuthHeaders())
            ->deleteJson('/api/gdpr-removal/7654324')
            ->assertStatus(202)
            ->assertJson(['status' => 'approved']);
    });

    test('is answered with 200 once the request is completed', function () {
        apiCreateKey(['gdpr.delete']);
        GdprRemovalRequest::create(['user_id' => null, 'vatsim_id' => 7654325, 'status' => 'completed']);

        $this->withHeaders(apiAuthHeaders())
            ->deleteJson('/api/gdpr-removal/7654325')
            ->assertOk();
    });
});

describe('GdprController data export', function () {
    test('requires the gdpr.read permission', function () {
        $this->getJson('/api/gdpr-data/1234567')->assertStatus(401);

        apiCreateKey(['gdpr.delete']);
        $this->withHeaders(apiAuthHeaders())->getJson('/api/gdpr-data/1234567')->assertStatus(403);
    });

    test('returns 404 for an unknown user', function () {
        apiCreateKey(['gdpr.read']);

        $this->withHeaders(apiAuthHeaders())->getJson('/api/gdpr-data/9999999')->assertNotFound();
    });

    test('returns the profile and everything stored about the user, without secrets', function () {
        apiCreateKey(['gdpr.read']);
        $user = User::factory()->create(['vatsim_id' => 7654330, 'email' => 'max@example.com']);
        $mentor = User::factory()->create();
        $course = Course::factory()->create();

        WaitingListEntry::create(['user_id' => $user->id, 'course_id' => $course->id]);
        createTrainingLog(['trainee_id' => $user->id, 'mentor_id' => $mentor->id, 'course_id' => $course->id]);
        createTrainingLog(['trainee_id' => $mentor->id, 'mentor_id' => $user->id]);

        $response = $this->withHeaders(apiAuthHeaders())
            ->getJson('/api/gdpr-data/7654330')
            ->assertOk()
            ->assertJsonPath('user.vatsim_id', 7654330)
            ->assertJsonPath('user.email', 'max@example.com')
            ->assertJsonPath('data.waiting_list_entries.count', 1)
            ->assertJsonPath('data.training_logs.count', 1)
            ->assertJsonPath('data.training_logs.kind', 'own_data')
            ->assertJsonPath('data.training_logs_as_mentor.count', 1)
            ->assertJsonPath('data.training_logs_as_mentor.kind', 'written_by_user_on_other_records');

        $response->assertJsonMissingPath('user.password')->assertJsonMissingPath('user.remember_token');
    });
});
