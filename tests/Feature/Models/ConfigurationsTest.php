<?php

use App\Models\Channels;
use App\Models\Configurations;
use App\Models\Environments;
use App\Models\Namespaces;
use App\Models\Services;
use App\Models\ServicesEnvironments;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);


it('can create, update, and delete a configurations', function () {
    // CREATE
    $ns = Namespaces::query()->create(['name' => 'namespace']);
    expect($ns->name)->toBe('namespace');

    $env = Environments::query()->create([
        'name' => 'testing',
    ]);

    $svc = Services::query()->create([
        'name' => 'service',
        'namespace_id' => $ns->id,
    ]);

    $svcEnv = ServicesEnvironments::query()->create([
        'service_id'     => $svc->id,
        'environment_id' => $env->id,
    ]);

    $chan = Channels::query()->create([
        'name' => 'slack',
    ]);

    $conf = Configurations::query()->create([
       'service_environment_id' => $svcEnv->id,
        'channel_id' => $chan->id
    ]);

    $conf->load(['serviceEnvironment', 'channel']);

    expect($conf->serviceEnvironment->id)->toBe($svcEnv->id);
    expect($conf->channel->name)->toBe($chan->name);

    // UPDATE — pindahkan ke service lain
    $svcOne = Services::query()->create([
        'name' => 'service-one',
        'namespace_id' => $ns->id,
    ]);
    $svcEnvOne = ServicesEnvironments::query()->create([
        'service_id' => $svcOne->id,
        'environment_id' => $env->id,
    ]);
    $conf->update([ 'service_environment_id' => $svcEnvOne->id ]);
    $conf->refresh();
    expect($conf->serviceEnvironment->id)->toBe($svcEnvOne->id);

    // DELETE
    $id = $conf->id;
    $conf->delete();
    $this->assertDatabaseMissing($conf->getTable(), ['id' => $id]);

    $svcEnvOne->refresh();
    expect($svcEnvOne->configurations)->toHaveCount(0);
});


it('can read with include all relationships configurations', function () {
    $ns  = Namespaces::create(['name' => 'ns']);
    $env = Environments::create(['name' => 'dev']);
    $svc = Services::create(['name' => 'svc', 'namespace_id' => $ns->id]);

    $svcEnv = ServicesEnvironments::create([
        'service_id' => $svc->id,
        'environment_id' => $env->id,
    ]);

    $chan = Channels::create(['name' => 'slack']);

    $conf = Configurations::create([
        'service_environment_id' => $svcEnv->id,
        'channel_id' => $chan->id,
    ])->load(['channel','serviceEnvironment']);

    expect($conf->channel->name)->toBe('slack');
    expect($conf->serviceEnvironment->id)->toBe($svcEnv->id);
});


it('can create, update and delete a configurations with service environments models', function () {
    // Create
    $ns = Namespaces::query()->create(['name' => 'namespace']);
    $env = Environments::query()->create(['name' => 'unit-testing']);
    $svc = Services::query()->create([
        'name' => 'service',
        'namespace_id' => $ns->id
    ]);
    $chan = Channels::query()->create([
        'name' => 'slack',
    ]);
    $svcOne = Services::query()->create([
        'name' => 'service-one',
        'namespace_id' => $ns->id,
    ]);
    $svcEnv = ServicesEnvironments::query()->create([
        'service_id' => $svc->id,
        'environment_id' => $env->id,
    ]);
    $conf = Configurations::query()->create([
        'channel_id' => $chan->id,
        'service_environment_id' => $svcEnv->id
    ]);
    $conf->load(['channel', 'serviceEnvironment']);

    expect($conf->channel->id)->not->toBeEmpty();
    expect($conf->serviceEnvironment->id)->not->toBeEmpty();

    // Update
    $conf->serviceEnvironment()->update([
        'service_id' => $svcOne->id
    ]);
    $conf->refresh();
    $conf = $conf->load(['channel', 'serviceEnvironment', 'serviceEnvironment.service']);
    expect($conf->serviceEnvironment->service->id)->toBe($svcOne->id);

    expect($conf->serviceEnvironment->service->id)->not->toBeEmpty();
    // Delete
    $conf->delete();

    $svcEnv->refresh();
    expect(count($svcEnv->configurations))->toBe(0);
});
