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

use App\Actions\CancelOrderAction;
use App\Actions\ProcessOrderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {
    }

    /**
     * Display a listing of orders.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->input('per_page', 15);
        $status = $request->input('status');

        $user = $request->user();

        if ($user->isAdmin()) {
            $orders = $status
                ? $this->orderService->getOrdersByStatus($status, $perPage)
                : $this->orderService->getAllOrders($perPage);
        } elseif ($user->isVendor()) {
            $orders = $this->orderService->getOrdersByVendor($user->id, $perPage);
        } else {
            $orders = $this->orderService->getOrdersByCustomer($user->id, $perPage);
        }

        return OrderResource::collection($orders);
    }

    /**
     * Store a newly created order.
     */
    public function store(CreateOrderRequest $request, ProcessOrderAction $action): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['customer_id'] = $request->user()->id;

            $order = $action->execute($data);

            return response()->json([
                'message' => 'Order created successfully',
                'data' => new OrderResource($order),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Order creation failed',
                'error' => app()->isLocal() ? $e->getMessage() : 'An error occurred.',
            ], 422);
        }
    }

    /**
     * Display the specified order.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        if (!$this->orderService->canAccessOrder($order, $request->user()->id, $request->user()->role->slug)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Load relationships - filter items for vendors
        $user = $request->user();
        if ($user->isVendor()) {
            $order->load([
                'customer',
                'items' => function ($query) use ($user) {
                    $query->whereHas('product', function ($q) use ($user) {
                        $q->where('vendor_id', $user->id);
                    });
                },
                'items.product',
                'items.productVariant'
            ]);
        } else {
            $order->load(['customer', 'items.product', 'items.productVariant']);
        }

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Update order status.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $status = $request->input('status');

        if ($status === 'processing') {
            $this->orderService->confirmOrder($id);
        } else {
            $this->orderService->updateOrderStatus($id, $status);
        }

        return response()->json([
            'message' => 'Order status updated successfully',
            'data' => new OrderResource($this->orderService->getOrderById($id)),
        ]);
    }

    /**
     * Cancel an order.
     */
    public function cancel(Request $request, int $id, CancelOrderAction $action): JsonResponse
    {
        try {
            $order = $this->orderService->getOrderById($id);

            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            if (!$this->orderService->canAccessOrder($order, $request->user()->id, $request->user()->role->slug)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $action->execute($id);

            return response()->json([
                'message' => 'Order cancelled successfully',
                'data' => new OrderResource($this->orderService->getOrderById($id)),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Order cancellation failed',
                'error' => app()->isLocal() ? $e->getMessage() : 'An error occurred.',
            ], 422);
        }
    }
}
