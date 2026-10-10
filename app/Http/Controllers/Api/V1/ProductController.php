<?php
/**
 * OMS API  -  Order Management System
 * Production code from  (SFG) | seofg.xyz
 * Built by Reabot6  | github.com/reabot6
 *
 * This file is part of the oms-api open source release.
 * Licensed under the MIT License.
 */

namespace App\Http\Controllers\Api\V1;

use App\Actions\ImportProductsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Http\Requests\ImportProductsRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {
    }

    /**
     * Display a listing of products.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');

        if ($search && $request->user()->isVendor()) {
            // Vendors search only within their own catalogue
            $products = $this->productService->searchProductsByVendor($search, $request->user()->id, $perPage);
        } elseif ($search) {
            $products = $this->productService->searchProducts($search, $perPage);
        } elseif ($request->user()->isVendor()) {
            $products = $this->productService->getProductsByVendor($request->user()->id, $perPage);
        } else {
            $products = $this->productService->getAllProducts($perPage);
        }

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created product.
     */
    public function store(ProductRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['vendor_id'] = $request->user()->isVendor()
            ? $request->user()->id
            : $data['vendor_id'];

        $product = $this->productService->createProduct($data);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => new ProductResource($product),
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $product->load(['vendor', 'variants', 'inventory']);

        return response()->json([
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(ProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        if (!$this->productService->canManageProduct($product, $request->user()->id, $request->user()->role->slug)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->productService->updateProduct($id, $request->validated());
        $product->refresh();

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        if (!$this->productService->canManageProduct($product, $request->user()->id, $request->user()->role->slug)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->productService->deleteProduct($id);

        return response()->json([
            'message' => 'Product deleted successfully',
        ]);
    }

    /**
     * Import products from CSV.
     */
    public function import(ImportProductsRequest $request, ImportProductsAction $action): JsonResponse
    {
        $file = $request->file('file');
        $vendorId = $request->user()->isVendor()
            ? $request->user()->id
            : $request->input('vendor_id');

        $result = $action->execute($file->getRealPath(), $vendorId);

        return response()->json([
            'message' => 'Import completed',
            'data' => $result,
        ]);
    }
}
