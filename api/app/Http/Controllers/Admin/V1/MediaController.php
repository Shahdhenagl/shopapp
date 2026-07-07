<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\StoreMediaRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class MediaController extends Controller
{
    public function store(StoreMediaRequest $request): JsonResponse
    {
        $path = $request->file('file')->store('media', 'public');

        return response()->json([
            'data' => [
                'url' => URL::to(Storage::disk('public')->url($path)),
            ],
        ], 201);
    }
}
