<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Domain\Admin\Support\MetricsService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class MetricsController extends Controller
{
    public function __construct(
        private readonly MetricsService $metrics,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->metrics->snapshot()]);
    }
}
