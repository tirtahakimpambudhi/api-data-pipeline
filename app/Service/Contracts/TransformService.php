<?php

namespace App\Service\Contracts;

use App\Http\Resources\Configurations\Destination;

interface TransformService
{
    public function buildPayloads($sourceBody, Destination $dst): array;
}
