<?php

use App\Http\Resources\Configurations\Destination;
use App\Http\Resources\Configurations\Source;
use App\Models\Channels;
use App\Models\Configurations;
use App\Models\Environments;
use App\Models\Namespaces;
use App\Models\Services;
use App\Models\ServicesEnvironments;
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

it('can read with include all relationships namespace', function () {
    $ns   = Namespaces::create(['name' => 'ns']);
    $env1 = Environments::create(['name' => 'dev']);
    $env2 = Environments::create(['name' => 'staging']);

    $svc1 = Services::create(['name' => 'svc1', 'namespace_id' => $ns->id]);
    $svc2 = Services::create(['name' => 'svc2', 'namespace_id' => $ns->id]);

    $se1 = ServicesEnvironments::create(['service_id' => $svc1->id, 'environment_id' => $env1->id]);
    $se2 = ServicesEnvironments::create(['service_id' => $svc1->id, 'environment_id' => $env2->id]);
    $se3 = ServicesEnvironments::create(['service_id' => $svc2->id, 'environment_id' => $env1->id]);

    $chan = Channels::create(['name' => 'slack']);
    Configurations::create([
        'service_environment_id' => $se1->id,
        'channel_id' => $chan->id,
        'source' => Source::fromArray([
            'url' => 'https://google.com'
        ]),
        'destination' => Destination::fromArray([
            'url' => 'https://google.com',
            'body_template' => json_encode([])
        ]),
        'cron_expression' => '* * * * *'
    ]);

    $ns->load(['services.environments','services.configurations','servicesEnvironments']);

    expect($ns->services)->toHaveCount(2);
    expect($ns->servicesEnvironments)->toHaveCount(3);
    expect($ns->services->pluck('environments')->flatten())->not->toBeEmpty();
    expect($ns->services->pluck('configurations')->flatten())->not->toBeEmpty();
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
