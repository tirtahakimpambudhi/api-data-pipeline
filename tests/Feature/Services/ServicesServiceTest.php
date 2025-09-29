<?php

use App\Exceptions\NotFoundServiceException;
use App\Exceptions\PermissionDeniedServiceException;
use App\Exceptions\UnauthorizedServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Models\Namespaces;
use App\Models\Permissions;
use App\Models\Roles;
use App\Models\Services;
use App\Models\Users;
use App\Service\Implements\ServicesServiceImpl;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Logger;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Mockery as M;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

afterEach(function () {
    M::close();
});

/**
 * Helpers
 */
function makeUserWithServicePerms(array $actions): Users
{
    $role = Roles::create(['name' => 'Admin']);
    foreach ($actions as $act) {
        $perm = Permissions::create(['resource_type' => 'services', 'action' => $act]);
        $role->permissions()->attach($perm->id);
    }
    return Users::factory()->for($role, 'role')->create();
}

function makeServiceImpl(?Users $user = null): ServicesServiceImpl
{
    $authFactory = M::mock(AuthFactory::class);
    $guard = M::mock(StatefulGuard::class);

    // user() bisa null (unauthorized) atau instance Users
    $guard->shouldReceive('user')->andReturn($user);
    $authFactory->shouldReceive('guard')->with('web')->andReturn($guard);

    $logger = M::mock(Logger::class)->shouldIgnoreMissing();

    // pakai instance model nyata
    return new ServicesServiceImpl($authFactory, new Services(), $logger);
}

function makeServiceRecord(string $name = 'svc', ?int $namespaceId = null): Services
{
    $nsId = $namespaceId ?? Namespaces::create(['name' => uniqid('ns_', true)])->id;
    return Services::create(['name' => $name, 'namespace_id' => $nsId]);
}

/**
 * checkPermission (via getAll) — unauthorized
 */
it('throws UnauthorizedServiceException when user is not authenticated', function () {
    $svc = makeServiceImpl(null); // guard->user() = null

    $req = M::mock(PaginationRequest::class);
    $req->shouldReceive('validated')->andReturn(['page' => 0, 'size' => 0]);

    $this->expectException(UnauthorizedServiceException::class);
    $svc->getAll($req);
});

/**
 * checkPermission (via getAll) — permission denied
 */
it('throws PermissionDeniedServiceException when user lacks read permission', function () {
    $user = makeUserWithServicePerms([]); // no perms
    $svc  = makeServiceImpl($user);

    $req = M::mock(PaginationRequest::class);
    $req->shouldReceive('validated')->andReturn(['page' => 0, 'size' => 0]);

    $this->expectException(PermissionDeniedServiceException::class);
    $svc->getAll($req);
});

/**
 * getAll — non-paginated success
 */
it('returns all services when page/size not provided', function () {
    $user = makeUserWithServicePerms(['read']);
    $svc  = makeServiceImpl($user);

    Namespaces::factory()->count(1)->create(); // optional, jika factory butuh
    makeServiceRecord('a1');
    makeServiceRecord('a2');
    makeServiceRecord('a3');

    $req = M::mock(PaginationRequest::class);
    $req->shouldReceive('validated')->andReturn(['page' => 0, 'size' => 0]);

    $result = $svc->getAll($req);
    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($result->count())->toBe(3);
});

/**
 * getAll — paginated success
 */
it('returns paginated services when page/size provided', function () {
    $user = makeUserWithServicePerms(['read']);
    $svc  = makeServiceImpl($user);

    // 23 services
    for ($i = 1; $i <= 23; $i++) {
        makeServiceRecord("svc-$i");
    }

    $req = M::mock(PaginationRequest::class);
    $req->shouldReceive('validated')->andReturn(['page' => 2, 'size' => 10]);

    $result = $svc->getAll($req);
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->currentPage())->toBe(2)
        ->and($result->perPage())->toBe(10)
        ->and($result->total())->toBe(23)
        ->and($result->count())->toBe(10);
});

/**
 * getById — success
 */
it('returns a single service by id', function () {
    $user = makeUserWithServicePerms(['read']);
    $svc  = makeServiceImpl($user);

    $rec = makeServiceRecord('solo');

    $result = $svc->getById($rec->id);
    expect($result->get('id'))->toBe($rec->id)
        ->and($result->get('name'))->toBe('solo');
});

/**
 * getById — not found
 */
it('throws NotFoundServiceException when service id not found', function () {
    $user = makeUserWithServicePerms(['read']);
    $svc  = makeServiceImpl($user);

    $this->expectException(NotFoundServiceException::class);
    $svc->getById(999999);
});

/**
 * search — non-paginated success (no search term)
 */
it('search without term returns all', function () {
    $user = makeUserWithServicePerms(['read']);
    $svc  = makeServiceImpl($user);

    makeServiceRecord('alpha');
    makeServiceRecord('beta');

    $req = M::mock(SearchPaginationRequest::class);
    $req->shouldReceive('validated')->andReturn(['page' => 0, 'size' => 0 /* no 'search' key */]);

    $result = $svc->search($req);
    expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($result->count())->toBe(2);
});

/**
 * search — paginated success (with term)
 *
 */
it('search with term returns filtered results (paginated)', function () {
    $user = makeUserWithServicePerms(['read']);
    $svc  = makeServiceImpl($user);

    makeServiceRecord('alpha');
    makeServiceRecord('alpine');
    makeServiceRecord('beta');

    $req = M::mock(SearchPaginationRequest::class);
    $req->shouldReceive('validated')->andReturn([
        'search' => 'alp',
        'page'   => 1,
        'size'   => 10,
    ]);

    $result = $svc->search($req);
    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and(collect($result->items())->pluck('name')->values()->all())
        ->toEqualCanonicalizing(['alpha', 'alpine']);
});

/**
 * create — success
 */
it('creates a new service', function () {
    $user = makeUserWithServicePerms(['create']);
    $svc  = makeServiceImpl($user);

    $ns = Namespaces::create(['name' => 'ns-create']);

    $req = M::mock(\App\Http\Requests\Services\CreateServiceRequest::class);
    $req->shouldReceive('validated')->andReturn([
        'name' => 'created-svc',
        'namespace_id' => $ns->id,
    ]);

    $result = $svc->create($req);
    $createdId = $result->get('id');

    expect($result->get('name'))->toBe('created-svc')
        ->and(Services::find($createdId))->not->toBeNull();
});

/**
 * create — permission denied
 */
it('create denies when user lacks permission', function () {
    $user = makeUserWithServicePerms([]); // no 'create'
    $svc  = makeServiceImpl($user);

    $ns = Namespaces::create(['name' => 'ns']);

    $req = M::mock(\App\Http\Requests\Services\CreateServiceRequest::class);
    $req->shouldReceive('validated')->andReturn([
        'name' => 'x', 'namespace_id' => $ns->id,
    ]);

    $this->expectException(PermissionDeniedServiceException::class);
    $svc->create($req);
});

/**
 * update — success (name + namespace_id)
 */
it('updates an existing service', function () {
    $user = makeUserWithServicePerms(['update']);
    $svc  = makeServiceImpl($user);

    $ns1 = Namespaces::create(['name' => 'ns-u1']);
    $ns2 = Namespaces::create(['name' => 'ns-u2']);

    $rec = makeServiceRecord('old-name', $ns1->id);

    $req = M::mock(\App\Http\Requests\Services\UpdateServiceRequest::class);
    $req->shouldReceive('validated')->andReturn([
        'name' => 'new-name',
        'namespace_id' => $ns2->id,
    ]);

    $result = $svc->update($rec->id, $req);
    expect($result->get('name'))->toBe('new-name')
        ->and($result->get('namespace_id'))->toBe($ns2->id);
});

/**
 * update — no updatable fields (skip)
 */
it('update skips when no valid fields provided', function () {
    $user = makeUserWithServicePerms(['update']);
    $svc  = makeServiceImpl($user);

    $ns  = Namespaces::create(['name' => 'ns-u']);
    $rec = makeServiceRecord('stay', $ns->id);

    $req = M::mock(\App\Http\Requests\Services\UpdateServiceRequest::class);
    // name hanya spasi, namespace_id kosong -> tidak ada payload
    $req->shouldReceive('validated')->andReturn(['name' => '   ']);

    $result = $svc->update($rec->id, $req);
    expect($result->get('name'))->toBe('stay')
        ->and($result->get('namespace_id'))->toBe($ns->id);
});

/**
 * update — not found
 */
it('throws NotFoundServiceException when updating non-existent service', function () {
    $user = makeUserWithServicePerms(['update']);
    $svc  = makeServiceImpl($user);

    $req = M::mock(\App\Http\Requests\Services\UpdateServiceRequest::class);
    $req->shouldReceive('validated')->andReturn(['name' => 'x']);

    $this->expectException(NotFoundServiceException::class);
    $svc->update(999999, $req);
});

/**
 * delete — success
 */
it('deletes a service', function () {
    $user = makeUserWithServicePerms(['delete']);
    $svc  = makeServiceImpl($user);

    $rec = makeServiceRecord('to-del');

    $result = $svc->delete($rec->id);
    expect($result->get('deleted'))->toBeTrue()
        ->and(Services::find($rec->id))->toBeNull();
});

/**
 * delete — not found
 */
it('throws NotFoundServiceException when deleting non-existent service', function () {
    $user = makeUserWithServicePerms(['delete']);
    $svc  = makeServiceImpl($user);

    $this->expectException(NotFoundServiceException::class);
    $svc->delete(999999);
});
