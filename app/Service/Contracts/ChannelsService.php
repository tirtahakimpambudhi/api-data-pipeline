<?php

namespace App\Service\Contracts;

use App\Http\Requests\Channels\CreateChannelRequest;
use App\Http\Requests\Channels\UpdateChannelRequest;
use App\Http\Requests\General\PaginationRequest;
use App\Http\Requests\General\SearchPaginationRequest;
use App\Http\Requests\ServiceEnvironment\CreateServiceEnvironmentRequest;
use App\Http\Requests\ServiceEnvironment\UpdateServiceEnvironmentRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ChannelsService
{
    public function getAll(PaginationRequest $data): LengthAwarePaginator | Collection;

    public function getById(int $id): Collection;

    public function search(SearchPaginationRequest $data): LengthAwarePaginator | Collection;

    public function create(CreateChannelRequest $data): Collection;
    public function update(int $id, UpdateChannelRequest $data): Collection;
    public function delete(int $id): Collection;
}
