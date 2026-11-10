<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderItemResource;
use App\Services\Api\OrderQueryService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderItemController extends Controller
{
    public function __construct(private readonly OrderQueryService $orders) {}

    public function index(string $orderNo): AnonymousResourceCollection
    {
        return OrderItemResource::collection($this->orders->findByOrderNo($orderNo)->items);
    }
}
