<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Catalog\Actions\Admin\ModerateReviewAction;
use App\Domain\Catalog\Contracts\AdminReviewRepositoryInterface;
use App\Domain\Catalog\Models\ProductReview;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Reviews\UpdateReviewRequest;
use App\Http\Resources\Admin\AdminReviewResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ReviewController extends Controller
{
    public function __construct(
        private readonly AdminReviewRepositoryInterface $reviews,
        private readonly ModerateReviewAction $moderateAction,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $paginator = $this->reviews->paginate(
            $request->query('status'),
            $request->query('product'),
            (int) $request->query('per_page', 20),
        );

        return AdminReviewResource::collection($paginator);
    }

    public function update(UpdateReviewRequest $request, string $id): AdminReviewResource
    {
        $review = $this->findOrFail($id);

        $review = $this->moderateAction->setStatus(
            $this->actor($request),
            $review,
            $request->validated('status'),
        );

        return AdminReviewResource::make($review);
    }

    public function destroy(Request $request, string $id): Response
    {
        $review = $this->findOrFail($id);

        $this->moderateAction->delete($this->actor($request), $review);

        return response()->noContent();
    }

    private function findOrFail(string $id): ProductReview
    {
        $review = $this->reviews->find($id);

        abort_if($review === null, 404, __('api.not_found'));

        return $review;
    }

    private function actor(Request $request): AdminUser
    {
        /** @var AdminUser $admin */
        $admin = $request->user();

        return $admin;
    }
}
