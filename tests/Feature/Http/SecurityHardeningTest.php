<?php

use App\Integrations\VatEud\VatEudService;
use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the legacy /api/user route no longer exists', function () {
    $this->getJson('/api/user')->assertNotFound();
});

test('api requests are rate limited, including ones with invalid keys', function () {
    foreach (range(1, 120) as $i) {
        $this->withHeader('Authorization', 'Bearer tc_invalid')->getJson('/api/cpts')->assertUnauthorized();
    }

    $this->withHeader('Authorization', 'Bearer tc_invalid')->getJson('/api/cpts')->assertStatus(429);
});

test('api 500 responses do not leak exception messages', function () {
    $key = ApiKey::create(['name' => 'test', 'permissions' => ['tier1.read'], 'is_active' => true]);

    $this->mock(VatEudService::class, function ($mock) {
        $mock->shouldReceive('getTier1Endorsements')->andThrow(new RuntimeException('secret internal detail'));
    });

    $response = $this->withHeader('Authorization', 'Bearer '.$key->plainKey)->getJson('/api/tier1');

    $response->assertStatus(500);
    expect($response->getContent())->not->toContain('secret internal detail');
});

test('forwarded client ip is only trusted from private proxies', function () {
    $key = ApiKey::create(['name' => 'ip', 'permissions' => ['cpts.read'], 'is_active' => true]);

    // The test client connects from 127.0.0.1, a trusted proxy, so X-Forwarded-For is honoured.
    $this->withHeaders(['Authorization' => 'Bearer '.$key->plainKey, 'X-Forwarded-For' => '203.0.113.9'])
        ->getJson('/api/cpts')->assertOk();

    expect($key->refresh()->last_used_ip)->toBe('203.0.113.9');

    // A public peer must not be able to spoof it.
    $this->withHeaders(['Authorization' => 'Bearer '.$key->plainKey, 'X-Forwarded-For' => '198.51.100.1'])
        ->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
        ->getJson('/api/cpts')->assertOk();

    expect($key->refresh()->last_used_ip)->toBe('8.8.8.8');
});
