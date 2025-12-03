<?php

namespace App\Service\Contracts;

use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Roles\CreateRolesRequest;
use App\Http\Requests\Roles\UpdateRolesRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface RolesService
{
    public function getAll(PaginationRequest | null $data, bool $onlyRoles = false): LengthAwarePaginator | Collection;

    public function getById(int $id): Collection;

    public function search(SearchPaginationRequest | null $data, bool $onlyRoles = false): LengthAwarePaginator | Collection;

    public function create(CreateRolesRequest $data): Collection;
    public function update(int $id, UpdateRolesRequest $data): Collection;
    public function delete(int $id): Collection;
}
