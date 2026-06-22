<?php

namespace App\Services\Management;

use App\Repositories\Management\InquiryRepositoryInterface;
use App\Models\ProjectInquiry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\InquiryProductImport;

class InquiryService
{
    protected $inquiryRepo;

    public function __construct(InquiryRepositoryInterface $inquiryRepo)
    {
        $this->inquiryRepo = $inquiryRepo;
    }

    /**
     * Generate sequential Inquiry Number: INQ-YYYYMM-XXXX
     */
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
        $data['inquiry_no'] = $this->generateInquiryNo();
        $data['status'] = 'Draft';
        return $this->inquiryRepo->create($data);
    }

    public function getInquiryDetails($id)
    {
        return $this->inquiryRepo->findById($id);
    }

    public function updateInquiry($id, array $data)
    {
        return $this->inquiryRepo->update($id, $data);
    }

    public function cancelInquiry($id)
    {
        return $this->inquiryRepo->update($id, ['status' => 'Cancelled']);
    }

    public function closeInquiry($id)
    {
        return $this->inquiryRepo->update($id, ['status' => 'Closed']);
    }

    /**
     * Import products from uploaded Excel file.
     */
    public function importProducts($inquiryId, $file)
    {
        $import = new InquiryProductImport($inquiryId);
        Excel::import($import, $file);
        
        return [
            'success' => true,
            'imported_count' => $import->getImportedCount(),
            'errors' => $import->getErrors(),
        ];
    }
}
