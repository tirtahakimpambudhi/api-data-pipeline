<?php

namespace App\Service\Contracts;

use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Users\CreateUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface UsersService
{
    public function getAll(PaginationRequest | null $data, bool $onlyUsers = false): LengthAwarePaginator | Collection;

    public function getById(string $id): Collection;

    public function search(SearchPaginationRequest | null $data, bool $onlyUsers = false): LengthAwarePaginator | Collection;

    public function create(CreateUserRequest $data): Collection;
    public function update(string $id, UpdateUserRequest $data): Collection;
    public function delete(string $id): Collection;
}
