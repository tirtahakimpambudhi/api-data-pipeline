<?php

use App\Http\Resources\Configurations\Destination;
use App\Http\Resources\Configurations\Source;
use App\Models\Channels;
use App\Models\Environments;
use App\Models\Namespaces;
use App\Models\Services;
use App\Models\ServicesEnvironments;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);


it('can create, update, and delete a channels', function () {
    // CREATE
    $chan = Channels::query()->create([
        'name' => 'slack',
    ]);
    expect($chan->name)->not->toBeEmpty();


    // UPDATE
    $chan->update(['name' => 'discord']);
    $chan->refresh();
    expect($chan->name)->toBe('discord');

    // DELETE
    $id = $chan->id;
    $chan->delete();

    $this->assertDatabaseMissing('channels', ['id' => $id]);
});

it('can create, update and delete a channels with configurations models', function () {
    // Create
    $ns = Namespaces::query()->create(['name' => 'namespace']);
    $env = Environments::query()->create(['name' => 'unit-testing']);
    $envOne = Environments::query()->create(['name' => 'unit-testing-one']);
    $svc = Services::query()->create([
        'name' => 'service',
        'namespace_id' => $ns->id
    ]);
    $chan = Channels::query()->create([
        'name' => 'slack',
    ]);
    $svcEnv = ServicesEnvironments::query()->create([
        'service_id' => $svc->id,
        'environment_id' => $env->id,
    ]);
    $svcEnvOne = ServicesEnvironments::query()->create([
        'service_id' => $svc->id,
        'environment_id' => $envOne->id,
    ]);
    $chan->configurations()->create([
        'service_environment_id' => $svcEnvOne->id,
        'source' => Source::fromArray([
            'url' => 'https://google.com'
        ]),
        'destination' => Destination::fromArray([
            'url' => 'https://google.com',
            'body_template' => json_encode([])
        ]),
        'cron_expression' => '* * * * *'
    ]);
    $chan->load(['servicesEnvironments', 'configurations']);

    expect(count($chan->configurations))->toBe(1);

    // Update
    $configurationsIds = $chan->configurations->pluck('id')->all();
    $chan->configurations()->find($configurationsIds[0])->update(
        [
            'service_environment_id' => $svcEnvOne->id
        ]
    );
    $chan->refresh();
    expect($chan->configurations->all()[0]['service_environment_id'])->toBe( $svcEnvOne->id);

    expect(count($chan->configurations))->toBe(1);
    // Delete
    $chan->configurations()->delete();
    $chan->refresh();

    expect(count($chan->configurations))->toBe(0);
});
