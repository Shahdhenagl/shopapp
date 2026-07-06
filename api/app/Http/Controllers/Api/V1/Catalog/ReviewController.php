<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Domain\Auth\Models\User;
use App\Domain\Catalog\Actions\CreateReviewAction;
use App\Domain\Catalog\Actions\ListReviewsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Catalog\CreateReviewRequest;
use App\Http\Resources\ProductReviewResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ListReviewsAction $listReviewsAction,
        private readonly CreateReviewAction $createReviewAction,
    ) {
    }

    public function index(string $id): AnonymousResourceCollection
    {
        return ProductReviewResource::collection(
            $this->listReviewsAction->execute($id)
        );
    }

    public function store(CreateReviewRequest $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $review = $this->createReviewAction->execute(
            $id,
            $user,
            (int) $request->validated('rating'),
            $request->validated('comment'),
        );

        return ProductReviewResource::make($review)
            ->response()
            ->setStatusCode(201);
    }
}
