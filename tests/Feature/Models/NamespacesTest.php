<?php

use App\Models\Namespaces;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can create, update, and delete a namespace', function () {
    // CREATE
    $ns = Namespaces::query()->create(['name' => 'namespace']);
    expect($ns->name)->toBe('namespace');

    // UPDATE
    $ns->update(['name' => 'namespace-2']);
    expect($ns->fresh()->name)->toBe('namespace-2');

    // DELETE
    $id = $ns->id;
    $ns->delete();

    $this->assertDatabaseMissing('namespaces', ['id' => $id]);
});

it('can read with include all relationships', function () {
    $this->seed(DatabaseSeeder::class);

    $ns = Namespaces::query()
        ->with('servicesEnvironments')
        ->with('services.environments')
        ->with('services.configurations')
        ->findOrFail(1);

    expect($ns->services)->not->toBeEmpty();
    expect($ns->servicesEnvironments)->not->toBeEmpty();

    $allServiceEnvs = $ns->services->pluck('environments')->flatten();
    $allServiceConfs = $ns->services->pluck('configurations')->flatten();
    expect($allServiceEnvs)->not->toBeEmpty();
    expect($allServiceConfs)->not->toBeEmpty();
});

it('can create, update and delete a namespace with service models', function () {
    // Create
    $ns = Namespaces::query()->create(['name' => 'namespace']);
    $ns->services()->createMany([['name' => 'service'], ['name' => 'service-2'], ['name' => 'service-3']]);
    //    $ns = $ns->newQuery()->with('services')->find($ns->id);
    $ns->load('services');
    expect(count($ns->services))->toBe(3);
    // Update
    $servicesIds = $ns->services->pluck('id')->all();
    $ns->services()->find($servicesIds[0])->update(
        [

            'name' => 'service-1',
        ]
    );
    $ns->load('services');
    expect($ns->services->all()[0]['name'])->toBe('service-1');
    expect(count($ns->services))->toBe(3);
    // Delete
    $ns->services()->find($servicesIds[1])->delete();
    $ns->load('services');
    expect(count($ns->services))->toBe(2);
});
