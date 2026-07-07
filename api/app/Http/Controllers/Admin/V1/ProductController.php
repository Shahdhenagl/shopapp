<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\V1;

use App\Domain\Admin\Models\AdminUser;
use App\Domain\Admin\Support\AuditLogger;
use App\Domain\Catalog\Actions\Admin\CreateProductAction;
use App\Domain\Catalog\Actions\Admin\DeleteProductAction;
use App\Domain\Catalog\Actions\Admin\UpdateProductAction;
use App\Domain\Catalog\Contracts\AdminProductRepositoryInterface;
use App\Domain\Catalog\Exceptions\ProductNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\Products\StoreProductRequest;
use App\Http\Requests\Admin\V1\Products\UpdateProductRequest;
use App\Http\Resources\Admin\AdminProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly AdminProductRepositoryInterface $products,
        private readonly CreateProductAction $createAction,
        private readonly UpdateProductAction $updateAction,
        private readonly DeleteProductAction $deleteAction,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $paginator = $this->products->paginate(
            $request->query('search'),
            $request->query('category'),
            (int) $request->query('per_page', 15),
        );

        return AdminProductResource::collection($paginator);
    }

    public function show(string $id): AdminProductResource
    {
        $product = $this->products->find($id)
            ?? throw new ProductNotFoundException;

        return AdminProductResource::make($product);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->createAction->execute($request->validated());

        $this->audit->log(
            $this->actor($request),
            'product.created',
            $product,
            null,
            AdminProductResource::make($product)->resolve($request),
        );

        return AdminProductResource::make($product)
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateProductRequest $request, string $id): AdminProductResource
    {
        $existing = $this->products->find($id)
            ?? throw new ProductNotFoundException;
        $before = AdminProductResource::make($existing)->resolve($request);

        $product = $this->updateAction->execute($id, $request->validated());

        $this->audit->log(
            $this->actor($request),
            'product.updated',
            $product,
            $before,
            AdminProductResource::make($product)->resolve($request),
        );

        return AdminProductResource::make($product);
    }

    public function destroy(Request $request, string $id): Response
    {
        $product = $this->deleteAction->execute($id);

        $this->audit->log(
            $this->actor($request),
            'product.deleted',
            $product,
            AdminProductResource::make($product)->resolve($request),
            null,
        );

        return response()->noContent();
    }

    private function actor(Request $request): AdminUser
    {
        /** @var AdminUser $admin */
        $admin = $request->user();

        return $admin;
    }
}
