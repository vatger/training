<?php

use App\Domain\Roster\Actions\CheckUserRosterStatus;
use App\Integrations\VatEud\VatEudClientInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    Event::fake();
});

// ─── Empty / failed roster ────────────────────────────────────────────────────

test('returns exit code 1 when roster is empty', function () {
    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getRoster')->once()->andReturn([]);
    $this->app->instance(VatEudClientInterface::class, $client);

    $this->artisan('roster:check')
        ->expectsOutputToContain('Failed to fetch roster')
        ->assertExitCode(1);
});

test('returns exit code 1 when client throws exception', function () {
    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getRoster')->once()->andThrow(new RuntimeException('Network error'));
    $this->app->instance(VatEudClientInterface::class, $client);

    $this->artisan('roster:check')
        ->expectsOutputToContain('Error during roster check')
        ->assertExitCode(1);
});

// ─── Successful runs ──────────────────────────────────────────────────────────

test('calls checkUserRosterStatus for each roster user and returns 0', function () {
    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getRoster')->once()->andReturn([1234567, 7654321]);
    $client->shouldReceive('getLastGermanSession')->andReturn(null);
    $this->app->instance(VatEudClientInterface::class, $client);

    $action = Mockery::mock(CheckUserRosterStatus::class);
    $action->shouldReceive('execute')->with(1234567)->once();
    $action->shouldReceive('execute')->with(7654321)->once();
    $this->app->instance(CheckUserRosterStatus::class, $action);

    $this->artisan('roster:check')
        ->expectsOutputToContain('Found 2 users on roster')
        ->expectsOutputToContain('Roster check completed successfully.')
        ->assertExitCode(0);
});

test('processes single-user roster', function () {
    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getRoster')->once()->andReturn([1234567]);
    $client->shouldReceive('getLastGermanSession')->andReturn(null);
    $this->app->instance(VatEudClientInterface::class, $client);

    $action = Mockery::mock(CheckUserRosterStatus::class);
    $action->shouldReceive('execute')->with(1234567)->once();
    $this->app->instance(CheckUserRosterStatus::class, $action);

    $this->artisan('roster:check')
        ->expectsOutputToContain('Found 1 users on roster')
        ->assertExitCode(0);
});

// ─── Per-user error handling ──────────────────────────────────────────────────

test('continues processing remaining users when one throws an exception', function () {
    $client = Mockery::mock(VatEudClientInterface::class);
    $client->shouldReceive('getRoster')->once()->andReturn([1111111, 2222222, 3333333]);
    $client->shouldReceive('getLastGermanSession')->andReturn(null);
    $this->app->instance(VatEudClientInterface::class, $client);

    $action = Mockery::mock(CheckUserRosterStatus::class);
    $action->shouldReceive('execute')->with(1111111)->once();
    $action->shouldReceive('execute')->with(2222222)->once()->andThrow(new RuntimeException('User error'));
    $action->shouldReceive('execute')->with(3333333)->once();
    $this->app->instance(CheckUserRosterStatus::class, $action);

    $this->artisan('roster:check')
        ->assertExitCode(0);
});
