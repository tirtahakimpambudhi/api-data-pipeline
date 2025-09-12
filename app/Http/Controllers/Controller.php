<?php

namespace App\Http\Controllers;

use App\Exceptions\AppServiceException;
use App\Exceptions\PermissionDeniedServiceException;
use App\Exceptions\UnauthorizedServiceException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    public function handleUnauthorizedAndPermissionDenied(
        AppServiceException $exception,
        Request $request
    ): ?RedirectResponse {
        if ($exception instanceof UnauthorizedServiceException
            || $exception instanceof PermissionDeniedServiceException) {

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', $exception->getMessage());
        }

        return null;
    }
}
