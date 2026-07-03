<?php

namespace App\Services\FeasibilityStudy;

use App\Repositories\FeasibilityStudy\InquiryRepository;
use App\Models\ProjectInquiry;
use App\Models\InquiryProduct;
use App\Imports\InquiryProductImport;
use Illuminate\Support\Facades\DB;

class InquiryService
{
    protected $inquiryRepo;

    public function __construct(InquiryRepository $inquiryRepo)
    {
        $this->inquiryRepo = $inquiryRepo;
    }

    public function generateInquiryNo()
    {
        $prefix = 'INQ-' . date('Ym') . '-';
        
        $lastInquiry = ProjectInquiry::withTrashed()
            ->where('inquiry_no', 'like', $prefix . '%')
            ->orderBy('inquiry_no', 'desc')
            ->first();

        if ($lastInquiry) {
            $parts = explode('-', $lastInquiry->inquiry_no);
            $lastSeq = (int) end($parts);
            $seq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $seq = '0001';
        }

        return $prefix . $seq;
    }

    public function paginateInquiries($perPage = 10, array $filters = [])
    {
        return $this->inquiryRepo->paginate($perPage, $filters);
    }

    public function createInquiry(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data['inquiry_no'] = $this->generateInquiryNo();
            $data['status'] = 'Draft';
            return $this->inquiryRepo->create($data);
        });
    }

    public function getInquiryDetails($id)
    {
        return $this->inquiryRepo->findById($id);
    }

    public function updateInquiry($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            return $this->inquiryRepo->update($id, $data);
        });
    }

    public function cancelInquiry($id)
    {
        return DB::transaction(function () use ($id) {
            return $this->inquiryRepo->update($id, ['status' => 'Cancelled']);
        });
    }

    public function closeInquiry($id)
    {
        return DB::transaction(function () use ($id) {
            return $this->inquiryRepo->update($id, ['status' => 'Closed']);
        });
    }

    public function importProducts($inquiryId, $file)
    {
        return DB::transaction(function () use ($inquiryId, $file) {
            $import = new InquiryProductImport($inquiryId);
            $import->import($file->getRealPath());
            
            return [
                'success' => true,
                'imported_count' => $import->getImportedCount(),
                'errors' => $import->getErrors(),
            ];
        });
    }

    public function finalizeInquiry($id)
    {
        return DB::transaction(function () use ($id) {
            $inquiry = ProjectInquiry::findOrFail($id);
            $inquiry->status = 'Active';
            $inquiry->save();
            return $inquiry;
        });
    }

    public function addProduct($inquiryId, array $productData)
    {
        return DB::transaction(function () use ($inquiryId, $productData) {
            return $this->inquiryRepo->addProduct($inquiryId, $productData);
        });
    }

    public function deleteProduct($productId)
    {
        return DB::transaction(function () use ($productId) {
            return $this->inquiryRepo->deleteProduct($productId);
        });
    }

    public function findProductById($productId)
    {
        return $this->inquiryRepo->findProductById($productId);
    }

    public function updateProduct($productId, array $productData)
    {
        return DB::transaction(function () use ($productId, $productData) {
            return $this->inquiryRepo->updateProduct($productId, $productData);
        });
    }

    public function savePriorityAssessment($productId, array $data, array $selections)
    {
        return DB::transaction(function () use ($productId, $data, $selections) {
            return $this->inquiryRepo->savePriorityAssessment($productId, $data, $selections);
        });
    }

    public function updateProductsOrder($orderedIds)
    {
        return DB::transaction(function () use ($orderedIds) {
            return $this->inquiryRepo->updateProductsOrder($orderedIds);
        });
    }

    public function forceDeleteInquiryWithProducts($id)
    {
        return DB::transaction(function () use ($id) {
            $inquiry = ProjectInquiry::findOrFail($id);
            $productIds = InquiryProduct::withTrashed()->where('inquiry_id', $id)->pluck('id')->all();
            if (!empty($productIds)) {
                $assessmentIds = DB::table('mng_inq_assessments')->whereIn('inquiry_product_id', $productIds)->pluck('id')->all();
                if (!empty($assessmentIds)) {
                    DB::table('mng_inq_assessment_details')->whereIn('assessment_id', $assessmentIds)->delete();
                    DB::table('mng_inq_assessments')->whereIn('inquiry_product_id', $productIds)->delete();
                }
                InquiryProduct::class;
                DB::table('mng_inquiry_products')->where('inquiry_id', $id)->delete();
            }
            $inquiry->forceDelete();
        });
    }
}
