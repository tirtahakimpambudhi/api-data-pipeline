<?php

namespace App\Service\Contracts;

use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\Namespaces\CreateNamespaceRequest;
use App\Http\Requests\Namespaces\CreateServiceRequest;
use App\Http\Requests\Namespaces\UpdateNamespaceRequest;
use App\Models\Namespaces;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface NamespacesService
{
    public function getAll(PaginationRequest | null $data, bool $onlyNamespace = false): LengthAwarePaginator | Collection;

    public function getById(int $id): Collection | null;

    public function search(SearchPaginationRequest | null $data, bool $onlyNamespace = false): LengthAwarePaginator | Collection;

    public function create(CreateNamespaceRequest $data): Collection;

    public function createService(int $id, CreateServiceRequest $data) : Collection;

    public function update(int $id, UpdateNamespaceRequest $data): Collection;
    public function delete(int $id): Collection;
}
