<?php

namespace App\Repositories\Management;

use App\Models\ProjectInquiry;
use App\Models\InquiryProduct;

interface InquiryRepositoryInterface
{
    public function paginate($perPage = 10, array $filters = []);
    public function findById($id);
    public function findByNo($inquiryNo);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function addProduct($inquiryId, array $productData);
    public function deleteProduct($productId);
    public function findProductById($productId);
}
