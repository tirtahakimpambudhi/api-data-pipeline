<?php

namespace App\Service\Contracts;

use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\ServiceEnvironment\CreateServiceEnvironmentRequest;
use App\Http\Requests\ServiceEnvironment\UpdateServiceEnvironmentRequest;
use App\Http\Requests\Services\CreateServiceRequest;
use App\Http\Requests\Services\UpdateServiceRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ServicesEnvironmentsService
{
    public function getAll(PaginationRequest | null $data, bool $onlySvcEnv = false): LengthAwarePaginator | Collection;

    public function getById(int $id): Collection;

    public function search(SearchPaginationRequest | null $data, bool $onlySvcEnv = false): LengthAwarePaginator | Collection;

    public function create(CreateServiceEnvironmentRequest $data): Collection;
    public function update(int $id, UpdateServiceEnvironmentRequest $data): Collection;
    public function delete(int $id): Collection;
}
