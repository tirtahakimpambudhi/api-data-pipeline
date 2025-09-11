<?php

namespace App\Service\Contracts;

use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Namespaces\CreateNamespaceRequest;
use App\Http\Requests\Namespaces\UpdateNamespaceRequest;
use App\Http\Requests\Services\CreateServiceRequest;
use App\Http\Requests\Services\UpdateServiceRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ServicesService
{
    public function getAll(PaginationRequest $data): LengthAwarePaginator | Collection;

    public function getById(int $id): Collection;

    public function search(SearchPaginationRequest $data): LengthAwarePaginator | Collection;

    public function create(CreateServiceRequest $data): Collection;
    public function update(int $id, UpdateServiceRequest $data): Collection;
    public function delete(int $id): Collection;
}
