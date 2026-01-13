<?php

use App\Http\Resources\Configurations\Destination;
use App\Http\Resources\Configurations\Source;
use App\Models\Channels;
use App\Models\Configurations;
use App\Models\Environments;
use App\Models\Namespaces;
use App\Models\Services;
use App\Models\ServicesEnvironments;
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
    $ns  = Namespaces::create(['name' => 'ns']);
    $env = Environments::create(['name' => 'dev']);

    $svc1 = Services::create(['name' => 'svc1', 'namespace_id' => $ns->id]);
    $svc2 = Services::create(['name' => 'svc2', 'namespace_id' => $ns->id]);

    $se1 = ServicesEnvironments::create(['service_id' => $svc1->id, 'environment_id' => $env->id]);
    $se2 = ServicesEnvironments::create(['service_id' => $svc2->id, 'environment_id' => $env->id]);

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
    Configurations::create([
        'service_environment_id' => $se2->id,
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

    $env->load(['services','servicesEnvironments','configurations']);

    expect($env->services)->toHaveCount(2);
    expect($env->servicesEnvironments)->toHaveCount(2);
    expect($env->configurations)->toHaveCount(2);
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
