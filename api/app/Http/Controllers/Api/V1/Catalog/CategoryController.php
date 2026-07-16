<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Catalog;

use App\Domain\Catalog\Actions\ListCategoriesAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function __construct(
        private readonly ListCategoriesAction $listCategoriesAction,
    ) {
    }

    /**
     * The whole tree, ordered by sort_order. Never paginated: clients build the
     * browse tree from the full set in one pass, so a page of it is useless.
     */
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection($this->listCategoriesAction->execute());
    }
}
