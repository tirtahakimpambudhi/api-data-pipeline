<?php

namespace App\Service\Contracts;

use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface NamespacesService
{
    public function getAll(PaginationRequest $data): LengthAwarePaginator | Collection;

    public function search(SearchPaginationRequest $data): LengthAwarePaginator | Collection;
}
