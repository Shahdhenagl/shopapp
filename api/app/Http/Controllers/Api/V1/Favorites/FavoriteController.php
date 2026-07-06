<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Favorites;

use App\Domain\Auth\Models\User;
use App\Domain\Favorites\Actions\ClearFavoritesAction;
use App\Domain\Favorites\Actions\ListFavoritesAction;
use App\Domain\Favorites\Actions\ToggleFavoriteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Favorites\ToggleFavoriteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FavoriteController extends Controller
{
    public function __construct(
        private readonly ListFavoritesAction $listFavoritesAction,
        private readonly ToggleFavoriteAction $toggleFavoriteAction,
        private readonly ClearFavoritesAction $clearFavoritesAction,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->listFavoritesAction->execute($this->user($request))]);
    }

    public function toggle(ToggleFavoriteRequest $request): JsonResponse
    {
        $ids = $this->toggleFavoriteAction->execute(
            $this->user($request),
            $request->validated('product_id'),
        );

        return response()->json(['data' => $ids]);
    }

    public function clear(Request $request): Response
    {
        $this->clearFavoritesAction->execute($this->user($request));

        return response()->noContent();
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
