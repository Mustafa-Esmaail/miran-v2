<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListOrdersRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Services\Api\OrderQueryService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    public function __construct(private readonly OrderQueryService $orders) {}

    public function index(ListOrdersRequest $request): AnonymousResourceCollection
    {
        return OrderResource::collection($this->orders->paginate($request->validated()));
    }

    public function show(string $orderNo): OrderResource
    {
        return new OrderResource($this->orders->findByOrderNo($orderNo));
    }
}
