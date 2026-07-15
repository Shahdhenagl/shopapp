<?php

namespace App\Http\Controllers\api\v1\catalog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Domain\Catalog\Models\SubCategory;

class SubCategoryController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subCategories = SubCategory::with('product')->get();
        return response()->json([
            'data' => \App\Http\Resources\SubCategoryResource::collection($subCategories),
        ]);
    }
}
