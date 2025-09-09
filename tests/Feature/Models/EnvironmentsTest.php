<?php

use App\Models\Environments;
use App\Models\Namespaces;
use App\Models\Services;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create, update, and delete a environment', function () {
    // CREATE
    $env = Environments::query()->create(['name' => 'environment']);
    expect($env->name)->toBe('environment');



    // UPDATE
    $env->update(['name' => 'environment-update']);
    expect($env->name)->toBe('environment-update');

    // DELETE
    $id = $env->id;
    $env->delete();

    $this->assertDatabaseMissing('environments', ['id' => $id]);

});

it('can read with include all relationships environment', function () {
    $this->seed(DatabaseSeeder::class);

    $env = Environments::query()
        ->with('servicesEnvironments')
        ->with('services')
        ->with('configurations')
        ->findOrFail(1);

    expect($env->services)->not->toBeEmpty();
    expect($env->servicesEnvironments)->not->toBeEmpty();
    $allServiceEnvs = $env->servicesEnvironments->flatten();
    $allServiceConfs = $env->configurations->flatten();
    expect($allServiceEnvs)->not->toBeEmpty();
    expect($allServiceConfs)->not->toBeEmpty();
});

it('can create, update and delete a environment with service environments models', function () {
    // Create
    $ns = Namespaces::query()->create(['name' => 'namespace']);
    $env = Environments::query()->create(['name' => 'unit-testing']);
    $svcOne = Services::query()->create([
        'name' => 'service-one',
        'namespace_id' => $ns->id
    ]);
    $svc = Services::query()->create([
        'name' => 'service',
        'namespace_id' => $ns->id
    ]);
    $env->servicesEnvironments()->create([
        'service_id' => $svc->id,
        'environment_id' => $env->id,
    ]);
    $env->load(['servicesEnvironments', 'services']);

    expect(count($env->servicesEnvironments))->toBe(1);
    // Update
    $svcEnvId = $env->servicesEnvironments->pluck('id')->all();
    $env->servicesEnvironments()->find($svcEnvId[0])->update(
        [
            'service_id' => $svcOne->id
        ]
    );
    $env->refresh();
    expect($env->servicesEnvironments->all()[0]['service_id'])->toBe( $svcOne->id);

    expect(count($env->servicesEnvironments))->toBe(1);
    // Delete
    $env->servicesEnvironments()->delete();
    $env->refresh();

    expect(count($env->servicesEnvironments))->toBe(0);
});
