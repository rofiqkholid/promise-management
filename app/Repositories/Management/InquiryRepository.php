<?php

namespace App\Repositories\Management;

use App\Models\ProjectInquiry;
use App\Models\InquiryProduct;

class InquiryRepository implements InquiryRepositoryInterface
{
    public function paginate($perPage = 10, array $filters = [])
    {
        $query = ProjectInquiry::with(['customer', 'projectModel']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('inquiry_no', 'like', "%{$search}%")
                  ->orWhere('project_name', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn($cq) => $cq->where('name', 'like', "%{$search}%")
                                                          ->orWhere('code', 'like', "%{$search}%"))
                  ->orWhereHas('projectModel', fn($mq) => $mq->where('name', 'like', "%{$search}%"));
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('inquiry_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('inquiry_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findById($id)
    {
        $inquiry = ProjectInquiry::with(['customer', 'projectModel', 'products.assessment.ranking'])->findOrFail($id);
        
        $sortedProducts = $inquiry->products->sort(function ($a, $b) {
            $sortA = $a->sort_order;
            $sortB = $b->sort_order;
            if ($sortA !== $sortB) {
                return $sortA <=> $sortB;
            }

            $scoreA = $a->assessment ? $a->assessment->total_score : 0;
            $scoreB = $b->assessment ? $b->assessment->total_score : 0;
            if ($scoreA !== $scoreB) {
                return $scoreB <=> $scoreA;
            }
            
            return $a->id <=> $b->id;
        })->values();

        $inquiry->setRelation('products', $sortedProducts);

        return $inquiry;
    }

    public function findByNo($inquiryNo)
    {
        return ProjectInquiry::where('inquiry_no', $inquiryNo)->first();
    }

    public function create(array $data)
    {
        return ProjectInquiry::create($data);
    }

    public function update($id, array $data)
    {
        $inquiry = ProjectInquiry::findOrFail($id);
        $inquiry->update($data);
        return $inquiry;
    }

    public function delete($id)
    {
        $inquiry = ProjectInquiry::findOrFail($id);
        return $inquiry->delete();
    }

    public function addProduct($inquiryId, array $productData)
    {
        $inquiry = ProjectInquiry::findOrFail($inquiryId);
        return $inquiry->products()->create($productData);
    }

    public function deleteProduct($productId)
    {
        $product = InquiryProduct::findOrFail($productId);
        return $product->delete();
    }

    public function findProductById($productId)
    {
        return InquiryProduct::with('inquiry')->findOrFail($productId);
    }
}
