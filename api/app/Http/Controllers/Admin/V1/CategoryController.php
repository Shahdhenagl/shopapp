<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Catalog\Actions\Admin\CreateCategoryAction;
use App\Domain\Catalog\Actions\Admin\DeleteCategoryAction;
use App\Domain\Catalog\Actions\Admin\UpdateCategoryAction;
use App\Domain\Catalog\Contracts\AdminCategoryRepositoryInterface;
use App\Domain\Catalog\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Categories\StoreCategoryRequest;
use App\Http\Requests\Admin\V1\Categories\UpdateCategoryRequest;
use App\Http\Resources\Admin\AdminCategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function __construct(
        private readonly AdminCategoryRepositoryInterface $categories,
        private readonly CreateCategoryAction $createCategoryAction,
        private readonly UpdateCategoryAction $updateCategoryAction,
        private readonly DeleteCategoryAction $deleteCategoryAction,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return AdminCategoryResource::collection($this->categories->tree());
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->createCategoryAction->execute($this->admin($request), $request->validated());

        return AdminCategoryResource::make($category)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateCategoryRequest $request, string $id): AdminCategoryResource
    {
        $category = $this->findOrFail($id);

        $category = $this->updateCategoryAction->execute(
            $this->admin($request),
            $category,
            $request->validated(),
        );

        return AdminCategoryResource::make($category);
    }

    public function destroy(Request $request, string $id): Response
    {
        $category = $this->findOrFail($id);

        $this->deleteCategoryAction->execute(
            $this->admin($request),
            $category,
            $request->boolean('cascade'),
        );

        return response()->noContent();
    }

    private function findOrFail(string $id): Category
    {
        $category = $this->categories->find($id);

        abort_if($category === null, 404, __('api.not_found'));

        return $category;
    }

    private function admin(Request $request): AdminUser
    {
        /** @var AdminUser $admin */
        $admin = $request->user();

        return $admin;
    }
}
