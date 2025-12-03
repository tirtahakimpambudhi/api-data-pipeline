<?php

namespace App\Service\Contracts;

use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PermissionsService
{
    public function getAll(PaginationRequest | null $data, bool $onlyPermissions = false): LengthAwarePaginator | Collection;

    public function getById(int $id): Collection;

    public function search(SearchPaginationRequest | null $data, bool $onlyPermissions = false): LengthAwarePaginator | Collection;
}
