<?php

use App\Models\Environments;
use App\Models\Namespaces;
use App\Models\Services;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create, update, and delete a service', function () {
    // CREATE
    $ns = Namespaces::query()->create(['name' => 'namespace']);
    expect($ns->name)->toBe('namespace');
    $svc = Services::query()->create([
        'name' => 'service',
        'namespace_id' => $ns->id
    ])->load('namespace');
    expect($svc->namespace->name)->toBe($ns->name);


    // UPDATE
    $ns = Namespaces::query()->create(['name' => 'namespace-1']);
    $svc->update(['namespace_id' => $ns->id]);
    $svc = $svc->refresh();
    expect($svc->namespace->name)->toBe($ns->name);

    // DELETE
    $id = $svc->id;
    $svc->delete();

    $this->assertDatabaseMissing('services', ['id' => $id]);
    $ns = $ns->refresh();
    $ns->load('services');
    expect(count($ns->services))->toBe(0);
});

it('can read with include all relationships service', function () {
    $this->seed(DatabaseSeeder::class);

    $svc = Services::query()
        ->with('namespace')
        ->with('servicesEnvironments')
        ->with('environments')
        ->with('configurations')
        ->findOrFail(1);

    expect($svc->namespace->name)->not->toBeEmpty();
    expect($svc->servicesEnvironments)->not->toBeEmpty();
    $allEnvs = $svc->environments->flatten();
    $allServiceConfs = $svc->configurations->flatten();
    expect($allEnvs)->not->toBeEmpty();
    expect($allServiceConfs)->not->toBeEmpty();
});

it('can create, update and delete a service with service environments models', function () {
    // Create
    $ns = Namespaces::query()->create(['name' => 'namespace']);
    $env = Environments::query()->create(['name' => 'unit-testing']);
    $envOne = Environments::query()->create(['name' => 'unit-testing-1']);
    $svc = Services::query()->create([
        'name' => 'service',
        'namespace_id' => $ns->id
    ]);
    $svc->servicesEnvironments()->create([
       'service_id' => $svc->id,
       'environment_id' => $env->id,
    ]);
    $svc->load(['servicesEnvironments', 'environments', 'namespace']);

    expect(count($svc->servicesEnvironments))->toBe(1);
    // Update
    $svcEnvId = $svc->servicesEnvironments->pluck('id')->all();
    $svc->servicesEnvironments()->find($svcEnvId[0])->update(
        [
            'environment_id' => $envOne->id
        ]
    );
    $svc->refresh();
    expect($svc->servicesEnvironments->all()[0]['environment_id'])->toBe( $envOne->id);

    expect(count($svc->servicesEnvironments))->toBe(1);
    // Delete
    $svc->servicesEnvironments()->delete();
    $svc->refresh();

    expect(count($svc->servicesEnvironments))->toBe(0);
});
