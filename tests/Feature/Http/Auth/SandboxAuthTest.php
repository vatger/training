<?php

use App\Support\SandboxAuth;

afterEach(function () {
    app()->detectEnvironment(fn () => 'testing');
});

test('sandbox routes return 404 when environment is production', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['services.vatger.sandbox_allowed_hosts' => '*']);

    $this->get('/auth/vatsim/sandbox')->assertNotFound();
    $this->get('/auth/vatsim/sandbox/callback')->assertNotFound();
});

test('sandbox routes return 404 when host is not in the allowed list', function () {
    config(['services.vatger.sandbox_allowed_hosts' => 'only-this-host.test']);

    $this->get('/auth/vatsim/sandbox')->assertNotFound();
});

test('sandbox redirect works when environment is non-production and host is allowed', function () {
    $host = parse_url(config('app.url'), PHP_URL_HOST);

    config([
        'services.vatger.sandbox_allowed_hosts' => $host,
        'services.vatger.oauth_sandbox_client_id' => 'test-client',
        'services.vatger.oauth_sandbox_redirect_uri' => "http://{$host}/auth/vatsim/sandbox/callback",
        'services.vatger.oauth_sandbox_auth_url' => 'https://auth-dev.vatsim.net/oauth/authorize',
    ]);

    $response = $this->get('/auth/vatsim/sandbox');

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('auth-dev.vatsim.net');
});

test('a non-default env alone does not enable sandbox auth without a matching host', function () {
    app()->detectEnvironment(fn () => 'local');
    config(['services.vatger.sandbox_allowed_hosts' => 'training.vatsim-germany.org']);

    expect(SandboxAuth::enabled(request()->create('http://production-lookalike.example')))->toBeFalse();
});

test('production environment always disables sandbox auth regardless of host', function () {
    app()->detectEnvironment(fn () => 'production');
    config(['services.vatger.sandbox_allowed_hosts' => '*']);

    expect(SandboxAuth::enabled(request()->create('http://localhost')))->toBeFalse();
});
