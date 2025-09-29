<?php

namespace App\Service\Contracts;

use App\Http\Requests\Configurations\CreateConfigurationRequest;
use App\Http\Requests\Configurations\UpdateConfigurationRequest;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\ServiceEnvironment\CreateServiceEnvironmentRequest;
use App\Http\Requests\ServiceEnvironment\UpdateServiceEnvironmentRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ConfigurationsService
{
    public function getAll(PaginationRequest | null $data, bool $onlyConf = false): LengthAwarePaginator | Collection;

    public function getById(int $id): Collection;

    public function search(SearchPaginationRequest | null $data, bool $onlyConf = false): LengthAwarePaginator | Collection;

    public function create(CreateConfigurationRequest $data): Collection;
    public function update(int $id, UpdateConfigurationRequest $data): Collection;
    public function delete(int $id): Collection;
}
