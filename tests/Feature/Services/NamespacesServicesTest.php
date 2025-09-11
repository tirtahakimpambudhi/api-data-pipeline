<?php

use App\Exceptions\ConflictServiceException;
use App\Exceptions\NotFoundServiceException;
use App\Exceptions\PermissionDeniedServiceException;
use App\Exceptions\UnauthorizedServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Namespaces\CreateNamespaceRequest;
use App\Http\Requests\Namespaces\CreateServiceRequest;
use App\Http\Requests\Namespaces\UpdateNamespaceRequest;
use App\Models\Namespaces;
use App\Service\Implements\NamespacesServiceImpl;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Logger;
use Illuminate\Support\Collection;
use Mockery as m;

uses(RefreshDatabase::class);


function makeServiceWithUser($canRead = true)
{
    // Mock Guard
    $guard = m::mock(StatefulGuard::class);
    $user  = m::mock();
    $user->name = 'Tester';
    $user->shouldReceive('hasPermission')
        ->with('namespaces', 'read')
        ->andReturn($canRead);

    // Default: authenticated
    $guard->shouldReceive('user')->andReturn($user);

    // Mock AuthFactory
    $authFactory = m::mock(AuthFactory::class);
    $authFactory->shouldReceive('guard')->with('web')->andReturn($guard);

    $logger = app(Logger::class);

    return new NamespacesServiceImpl($authFactory, new Namespaces(), $logger);
}

/* ======================
 * GET ALL
 * ====================== */

it('returns paginated namespaces (getAll)', function () {
    Namespaces::factory()->count(23)->create();

    $service = makeServiceWithUser(true);
    $req = \Mockery::mock(PaginationRequest::class);
    $req->shouldReceive('validated')->andReturn(['page' => 2, 'size' => 10]);

    $result = $service->getAll($req);
    expect($result->currentPage())->toBe(2)
        ->and($result->perPage())->toBe(10)
        ->and($result->total())->toBe(23)
        ->and($result->count())->toBe(10);
});

it('throws NotFoundServiceException when page out of range (getAll)', function () {
    Namespaces::factory()->count(5)->create();

    $service = makeServiceWithUser(true);
    $req = \Mockery::mock(PaginationRequest::class);
    $req->shouldReceive('validated')->andReturn(['page' => 2, 'size' => 10]);


    $service->getAll($req);
})->throws(NotFoundServiceException::class);

it('denies access when user missing read permission (getAll)', function () {
    Namespaces::factory()->count(1)->create();

    $service = makeServiceWithUser(false);
    $req = \Mockery::mock(PaginationRequest::class);
    $req->shouldReceive('validated')->andReturn(['page' => 2, 'size' => 10]);


    $service->getAll($req);
})->throws(PermissionDeniedServiceException::class);

it('denies access when unauthenticated (getAll)', function () {
    // Rebuild service where user() returns null
    $guard = m::mock(StatefulGuard::class);
    $guard->shouldReceive('user')->andReturn(null);
    $authFactory = m::mock(AuthFactory::class);
    $authFactory->shouldReceive('guard')->with('web')->andReturn($guard);

    $logger = app(Logger::class);
    $service = new NamespacesServiceImpl($authFactory, new Namespaces(), $logger);

    $req = \Mockery::mock(PaginationRequest::class);
    $req->shouldReceive('validated')->andReturn(['page' => 2, 'size' => 10]);

    $service->getAll($req);
})->throws(UnauthorizedServiceException::class);


/* ======================
 * SEARCH
 * ====================== */

it('search filters by name and paginates', function () {
    Namespaces::factory()
        ->count(3)
        ->state(new Sequence(
            ['name' => 'alpha-core'],
            ['name' => 'beta-core'],
            ['name' => 'gamma-ui']
        ))
        ->create();
    $service = makeServiceWithUser(true);
    $req = m::mock(SearchPaginationRequest::class);
    $req->shouldReceive('validated')->andReturn([
        'search' => 'core',
        'page'   => 1,
        'size'   => 10,
    ]);


    $result = $service->search($req);
    $names = collect($result->items())->pluck('name')->all();
    expect($result->total())->toBe(2)
        ->and($names)->toContain('alpha-core', 'beta-core')
        ->and($names)->not->toContain('gamma-ui');
});

it('search out-of-range page throws NotFoundServiceException', function () {
    Namespaces::factory()
        ->count(3)
        ->state(new Sequence(
            ['name' => 'alpha-core'],
            ['name' => 'beta-core'],
            ['name' => 'gamma-ui']
        ))
        ->create();

    $service = makeServiceWithUser(true);

    $req = m::mock(SearchPaginationRequest::class);
    $req->shouldReceive('validated')->andReturn([
        'search' => 'core',
        'page'   => 5,
        'size'   => 1,
    ]);


    $service->search($req);
})->throws(NotFoundServiceException::class);

function makeServiceWithGrants(array $grants)
{
    $guard = m::mock(StatefulGuard::class);

    $user = new class($grants) {
        public string $name = 'Tester';
        public function __construct(private array $grants) {}
        public function hasPermission(string $resource, string $action): bool
        {
            return ($resource === 'namespaces' || $resource === 'services') && in_array($action, $this->grants, true);
        }
    };

    $guard->shouldReceive('user')->andReturn($user);

    $authFactory = m::mock(AuthFactory::class);
    $authFactory->shouldReceive('guard')->with('web')->andReturn($guard);

    $logger = app(Logger::class);

    return new NamespacesServiceImpl($authFactory, new Namespaces(), $logger);
}

/**
 * Helper: mock CreateNamespaceRequest
 */
function mockCreateRequest(array $payload): CreateNamespaceRequest
{
    /** @var CreateNamespaceRequest $req */
    $req = m::mock(CreateNamespaceRequest::class);
    $req->shouldReceive('validated')->andReturn($payload);
    return $req;
}

/**
 * Helper: mock CreateServiceRequest
 */
function mockCreateServiceRequest(array $payload): CreateServiceRequest
{
    /** @var CreateServiceRequest $req */
    $req = m::mock(CreateServiceRequest::class);
    $req->shouldReceive('validated')->andReturn($payload);
    return $req;
}



/**
 * Helper: mock UpdateNamespaceRequest
 */
function mockUpdateRequest(array $payload): UpdateNamespaceRequest
{
    /** @var UpdateNamespaceRequest $req */
    $req = m::mock(UpdateNamespaceRequest::class);
    $req->shouldReceive('validated')->andReturn($payload);
    return $req;
}

/* ======================
 * CREATE SERVICE
 * ====================== */
it('create a service with namespace', function () {
    $service = makeServiceWithGrants(['create']);
    $namespace = Namespaces::factory()->create();

    $req = mockCreateServiceRequest(['name' => fake()->unique()->word()]);

    $result = $service->createService($namespace->id, $req);
    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->get('services'))->not->toBeEmpty();
    $this->assertDatabaseHas('namespaces', $result->forget(['services','created_at', 'updated_at'])->toArray());
});
/* ======================
 * CREATE
 * ====================== */

it('creates a namespace successfully', function () {
    $service = makeServiceWithGrants(['create']);

    $req = mockCreateRequest(['name' => 'platform-core']);

    $result = $service->create($req);
    expect($result)->toBeInstanceOf(Collection::class);

    // Verify persisted
    $this->assertDatabaseHas('namespaces', $result->forget(['created_at', 'updated_at'])->toArray());
});

it('throws ConflictServiceException when creating duplicate name', function () {
    Namespaces::factory()->create(['name' => 'dup-name']);

    $service = makeServiceWithGrants(['create']);
    $req = mockCreateRequest(['name' => 'dup-name']);

    $service->create($req);
})->throws(ConflictServiceException::class);

it('denies create when user lacks permission', function () {
    $service = makeServiceWithGrants(['read']); // not granting create
    $req = mockCreateRequest(['name' => 'nope']);

    $service->create($req);
})->throws(PermissionDeniedServiceException::class);

it('denies create when unauthenticated', function () {
    // Build service with null user
    $guard = m::mock(StatefulGuard::class);
    $guard->shouldReceive('user')->andReturn(null);
    $authFactory = m::mock(AuthFactory::class);
    $authFactory->shouldReceive('guard')->with('web')->andReturn($guard);
    $logger = app(Logger::class);
    $service = new NamespacesServiceImpl($authFactory, new Namespaces(), $logger);

    $req = mockCreateRequest(['name' => 'x']);
    $service->create($req);
})->throws(UnauthorizedServiceException::class);

/* ======================
 * UPDATE
 * ====================== */

it('updates namespace name when provided', function () {
    $ns = Namespaces::factory()->create(['name' => 'old-name']);

    $service = makeServiceWithGrants(['update']);
    $req = mockUpdateRequest(['name' => 'new-name']);

    $result = $service->update($ns->id, $req);

    expect($result)->toBeInstanceOf(Collection::class);
    expect($result->get('name'))->toBe('new-name');

    $this->assertDatabaseHas('namespaces', ['id' => $ns->id, 'name' => 'new-name']);
});

it('skips updating name when empty string provided', function () {
    $ns = Namespaces::factory()->create(['name' => 'keep-name']);

    $service = makeServiceWithGrants(['update']);
    $req = mockUpdateRequest(['name' => '   ']); // empty/whitespace → skip

    $result = $service->update($ns->id, $req);
    expect($result->get('name'))->toBe('keep-name');

    $this->assertDatabaseHas('namespaces', ['id' => $ns->id, 'name' => 'keep-name']);
});

it('throws NotFoundServiceException when updating non-existent id', function () {
    $service = makeServiceWithGrants(['update']);
    $req = mockUpdateRequest(['name' => 'anything']);

    $service->update(999999, $req);
})->throws(NotFoundServiceException::class);

it('throws ConflictServiceException when updating to a duplicate name', function () {
    $a = Namespaces::factory()->create(['name' => 'A']);
    $b = Namespaces::factory()->create(['name' => 'B']);

    $service = makeServiceWithGrants(['update']);
    $req = mockUpdateRequest(['name' => 'A']); // collide with other record

    $service->update($b->id, $req);
})->throws(ConflictServiceException::class);

it('denies update when user lacks permission', function () {
    $ns = Namespaces::factory()->create();

    $service = makeServiceWithGrants(['read']); // no update
    $req = mockUpdateRequest(['name' => 'x']);

    $service->update($ns->id, $req);
})->throws(PermissionDeniedServiceException::class);

/* ======================
 * DELETE
 * ====================== */

it('deletes namespace successfully', function () {
    $ns = Namespaces::factory()->create(['name' => 'todelete']);

    $service = makeServiceWithGrants(['delete']);

    $result = $service->delete($ns->id);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->toArray())->toMatchArray(['id' => $ns->id, 'deleted' => true]);

    $this->assertDatabaseMissing('namespaces', ['id' => $ns->id]);
});

it('throws NotFoundServiceException when deleting non-existent id', function () {
    $service = makeServiceWithGrants(['delete']);
    $service->delete(123456);
})->throws(NotFoundServiceException::class);

it('denies delete when user lacks permission', function () {
    $ns = Namespaces::factory()->create();

    $service = makeServiceWithGrants(['read']); // no delete
    $service->delete($ns->id);
})->throws(PermissionDeniedServiceException::class);

/* ======================
 * GET BY ID
 * ====================== */

it('gets namespace by id successfully', function () {
    $ns = Namespaces::factory()->create(['name' => 'lookup']);

    $service = makeServiceWithGrants(['read']);

    $result = $service->getById($ns->id);
    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->get('id'))->toBe($ns->id)
        ->and($result->get('name'))->toBe('lookup');

});

it('throws NotFoundServiceException when getting non-existent id', function () {
    $service = makeServiceWithGrants(['read']);
    $service->getById(999999);
})->throws(NotFoundServiceException::class);

it('denies getById when user lacks read permission', function () {
    $ns = Namespaces::factory()->create();

    $service = makeServiceWithGrants([]); // no read
    $service->getById($ns->id);
})->throws(PermissionDeniedServiceException::class);
