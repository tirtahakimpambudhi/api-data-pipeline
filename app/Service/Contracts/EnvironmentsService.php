<?php

namespace App\Service\Contracts;

use App\Http\Requests\Environments\CreateEnvironmentRequest;
use App\Http\Requests\Environments\UpdateEnvironmentRequest;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\ServiceEnvironment\CreateServiceEnvironmentRequest;
use App\Http\Requests\ServiceEnvironment\UpdateServiceEnvironmentRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface EnvironmentsService
{
    public function getAll(PaginationRequest | null $data, bool $onlyEnv = false): LengthAwarePaginator | Collection;

    public function getById(int $id): Collection;

    public function search(SearchPaginationRequest | null $data, bool $onlyEnv = false): LengthAwarePaginator | Collection;

    public function create(CreateEnvironmentRequest $data): Collection;
    public function update(int $id, UpdateEnvironmentRequest $data): Collection;
    public function delete(int $id): Collection;
}
