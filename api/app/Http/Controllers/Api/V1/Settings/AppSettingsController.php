<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Settings;

use App\Domain\Tenancy\Actions\ShowAppSettingsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\AppSettingsResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppSettingsController extends Controller
{
    public function __construct(
        private readonly ShowAppSettingsAction $showAppSettings,
    ) {
    }

    public function show(Request $request): JsonResource
    {
        return AppSettingsResource::make($this->showAppSettings->execute());
    }
}
