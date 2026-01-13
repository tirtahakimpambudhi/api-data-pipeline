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

it('can create, update, and delete a service environments 1', function () {
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

    // verifikasi relasi (lazy load ok, atau pakai load)
    expect($svcEnv->environment->name)->toBe($env->name);
    expect($svcEnv->service->name)->toBe($svc->name);

    // UPDATE — pindahkan ke service lain
    $svcOne = Services::query()->create([
        'name' => 'service-one',
        'namespace_id' => $ns->id, // jika FK wajib
    ]);

    // Cara A (direkomendasikan): associate agar FK & cache relasi konsisten
    $svcEnv->service()->associate($svcOne)->save();
    // Alternatif jika tetap update manual:
    // $svcEnv->update(['service_id' => $svcOne->id]);
    // $svcEnv->unsetRelation('service'); // atau $svcEnv = $svcEnv->fresh('service');

    expect($svcEnv->service->name)->toBe($svcOne->name);

    // DELETE
    $id = $svcEnv->id;
    $svcEnv->delete();
    $this->assertDatabaseMissing($svcEnv->getTable(), ['id' => $id]);

    // pastikan tidak ada lagi mapping pada $svcOne
    $svcOne->load('servicesEnvironments');
    expect($svcOne->servicesEnvironments)->toHaveCount(0);
});

it('can read with include all relationships service environment', function () {
    $ns  = Namespaces::create(['name' => 'ns']);
    $env = Environments::create(['name' => 'dev']);
    $svc = Services::create(['name' => 'svc', 'namespace_id' => $ns->id]);

    $se = ServicesEnvironments::create(['service_id' => $svc->id, 'environment_id' => $env->id]);

    $chan1 = Channels::create(['name' => 'slack']);
    $chan2 = Channels::create(['name' => 'discord']);
    Configurations::create([
        'service_environment_id' => $se->id,
        'channel_id' => $chan1->id,
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
        'service_environment_id' => $se->id,
        'channel_id' => $chan2->id,
            'source' => Source::fromArray([
        'url' => 'https://google.com'
    ]),
        'destination' => Destination::fromArray([
        'url' => 'https://google.com',
        'body_template' => json_encode([])
    ]),
        'cron_expression' => '* * * * *'
    ]);

    $se->load(['service','environment','configurations','channels']);

    expect($se->service->name)->toBe('svc');
    expect($se->environment->name)->toBe('dev');
    expect($se->channels)->toHaveCount(1);
    expect($se->configurations)->toHaveCount(2);
});

it('can create, update and delete a service environments with configurations models', function () {
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
    $chanOne = Channels::query()->create([
        'name' => 'tiktok',
    ]);
    $svcEnv = ServicesEnvironments::query()->create([
        'service_id' => $svc->id,
        'environment_id' => $env->id,
    ]);
    $svcEnv->configurations()->create([
        'channel_id' => $chan->id
    ]);
    $svcEnv->load(['service', 'environment', 'configurations']);

    expect(count($svcEnv->configurations))->toBe(1);

    // Update
    $configurationsIds = $svc->configurations->pluck('id')->all();
    $svcEnv->configurations()->find($configurationsIds[0])->update(
        [
            'channel_id' => $chanOne->id
        ]
    );
    $svcEnv->refresh();
    expect($svcEnv->configurations->all()[0]['channel_id'])->toBe( $chanOne->id);

    expect(count($svcEnv->configurations))->toBe(1);
    // Delete
    $svcEnv->configurations()->delete();
    $svcEnv->refresh();

    expect(count($svcEnv->configurations))->toBe(0);
});
