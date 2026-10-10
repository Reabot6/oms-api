<?php
/**
 * OMS API  -  Order Management System
 * Production code from  (SFG) | seofg.xyz
 * Built by Reabot6  | github.com/reabot6
 *
 * This file is part of the oms-api open source release.
 * Licensed under the MIT License.
 */

namespace App\Repositories\Contracts;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    public function all(): Collection;

    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function find(int $id): ?Order;

    public function findByOrderNumber(string $orderNumber): ?Order;

    public function create(array $data): Order;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function getByCustomer(int $customerId, int $perPage = 15): LengthAwarePaginator;

    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator;

    public function getByVendor(int $vendorId, int $perPage = 15): LengthAwarePaginator;
}
