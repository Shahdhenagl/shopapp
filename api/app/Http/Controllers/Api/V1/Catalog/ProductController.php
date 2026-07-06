<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Domain\Catalog\Actions\ListProductsAction;
use App\Domain\Catalog\Actions\ShowProductAction;
use App\Domain\Catalog\DTOs\ProductFilter;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(
        private readonly ListProductsAction $listProductsAction,
        private readonly ShowProductAction $showProductAction,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $newest = $request->query('newest');

        $filter = new ProductFilter(
            categoryId: $request->query('category'),
            search: $request->query('q'),
            newestOnly: $newest !== null ? filter_var($newest, FILTER_VALIDATE_BOOLEAN) : null,
        );

        return ProductResource::collection($this->listProductsAction->execute($filter));
    }

    public function show(string $id): ProductResource
    {
        return ProductResource::make($this->showProductAction->execute($id));
    }
}
