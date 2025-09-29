<?php

namespace App\Http\Controllers;

use App\Exceptions\AppServiceException;
use App\Exceptions\PermissionDeniedServiceException;
use App\Exceptions\UnauthorizedServiceException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;

abstract class Controller
{
    public function emptyPaginated(): array
    {
        return [
            'data' => [],
            'meta' => [
                'total'        => 0,
                'per_page'     => 0,
                'current_page' => 1,
                'last_page'    => 1,
            ],
        ];
    }


    public function handleUnauthorizedAndPermissionDenied(
        AppServiceException $exception,
        Request $request
    ): ?RedirectResponse {
        if ($exception instanceof UnauthorizedServiceException ) {

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', $exception->getMessage());
        }

        if ($exception instanceof PermissionDeniedServiceException ) {
            return redirect()->route('dashboard')->with('error', $exception->getMessage());
        }

        return null;
    }

    public function inertiaWithStatus(Response $resp, int $status)
    {
        return $resp->toResponse(request())->setStatusCode($status);
    }
}
