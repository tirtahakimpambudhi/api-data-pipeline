<?php

use App\Exceptions\NotFoundServiceException;
use App\Exceptions\PermissionDeniedServiceException;
use App\Exceptions\UnauthorizedServiceException;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Models\Namespaces;
use App\Service\Implements\NamespacesServiceImpl;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Logger;
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
