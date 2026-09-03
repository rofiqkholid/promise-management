<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeasibilityStudy\InquiryController;
use App\Http\Controllers\Management\AssessmentConfigController;
use App\Http\Controllers\FeasibilityStudy\WorkOrderController;
use App\Http\Controllers\FeasibilityStudy\WorkOrderToolingController;
use App\Http\Controllers\FeasibilityStudy\WorkOrderAddProcessController;
use App\Http\Controllers\FeasibilityStudy\WorkOrderFastenerController;
use App\Http\Controllers\Management\ApprovalConfigController;
use App\Http\Controllers\Management\CalendarController;
use App\Http\Controllers\FeasibilityStudy\EbdController;
use App\Http\Controllers\FeasibilityStudy\EbdRequestController;
use App\Http\Controllers\Management\MfgProcessCostController;
use App\Http\Controllers\Management\MfgProcessStpCostController;
use App\Http\Controllers\Management\MaterialCostController;
use App\Http\Controllers\Management\CustomerCostPolicyController;
use App\Http\Controllers\Management\ProductCostComparisonController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


Route::get('/login', function () {
    if (request()->has('redirect')) {
        session()->put('url.intended', request()->get('redirect'));
    }
    return redirect(config('services.portal_login_url'));
})->name('login');

Route::get('/', function () {
    Log::info('Inventory SSO Check', [
        'session_id' => session()->getId(),
        'cookie_val' => request()->cookie('promise_auth_session'),
        'auth_check' => Auth::check(),
        'user_id' => Auth::id(),
    ]);
    if (Auth::check()) {
        return redirect()->intended(route('dashboard'));
    }
    return redirect()->route('login');
});

Route::post('/login', function () {
    return redirect()->route('login');
})->name('login_post');
Route::post('/logout', function () {
    Auth::logout();
    session()->invalidate();
    return redirect(config('services.portal_login_url'));
})->name('logout');

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

Route::middleware('auth')->prefix('management')->name('management.')->group(function () {
    Route::get('ajax/users', [WorkOrderController::class, 'apiGetUsers'])->name('api.users');
    Route::get('ajax/suppliers', [\App\Http\Controllers\FeasibilityStudy\ToolingQuotationCompareController::class, 'apiGetSuppliers'])->name('api.suppliers');
    Route::get('ajax/exchange-rate', [\App\Http\Controllers\FeasibilityStudy\ToolingQuotationCompareController::class, 'apiGetExchangeRate'])->name('api.exchange-rate');
    Route::get('ajax/processes', [WorkOrderController::class, 'apiGetProcesses'])->name('api.processes');
    Route::get('ajax/approval-rules', [ApprovalConfigController::class, 'apiGetRules'])->name('api.approval-rules');

    // Chat Routes (Work Order, Inquiry, and any future module)
    Route::get('chats/file/{chatId}', [\App\Http\Controllers\Management\ChatController::class, 'showFile'])->name('chats.show-file');
    Route::get('chats/download-all/{chatId}', [\App\Http\Controllers\Management\ChatController::class, 'downloadAll'])->name('chats.download-all');
    Route::get('chats/download/{chatId}', [\App\Http\Controllers\Management\ChatController::class, 'download'])->name('chats.download');
    Route::delete('chats/{chatId}/attachment/{index}', [\App\Http\Controllers\Management\ChatController::class, 'destroyAttachment'])->name('chats.attachment.destroy');
    Route::delete('chats/{chatId}', [\App\Http\Controllers\Management\ChatController::class, 'destroy'])->name('chats.destroy');
    Route::get('chats/mentionables/{type}/{id}', [\App\Http\Controllers\Management\ChatController::class, 'getMentionables'])->name('chats.mentionables');
    Route::get('chats/{type}/{id}', [\App\Http\Controllers\Management\ChatController::class, 'index'])->name('chats.index');
    Route::post('chats/{type}/{id}', [\App\Http\Controllers\Management\ChatController::class, 'store'])->name('chats.store');

    // Inquiry Routes
    Route::middleware('check.menu:management.inquiry.index')->group(function () {
        Route::post('inquiry/{inquiry}/import', [InquiryController::class, 'import'])->name('inquiry.import');
        Route::get('inquiry-template/download', [InquiryController::class, 'downloadTemplate'])->name('inquiry.download-template');
        Route::post('inquiry/{inquiry}/parse-excel', [InquiryController::class, 'parseExcel'])->name('inquiry.parse-excel');
        Route::post('inquiry/{inquiry}/finalize', [InquiryController::class, 'finalize'])->name('inquiry.finalize');
        Route::get('inquiry-product/{id}/assess', [InquiryController::class, 'assessPage'])->name('inquiry-product.assess-page');
        Route::post('inquiry-product/{id}/assess', [InquiryController::class, 'assessProduct'])->name('inquiry-product.assess');
        Route::get('inquiry-product/{id}', [InquiryController::class, 'showProduct'])->name('inquiry-product.show');
        Route::patch('inquiry-product/{id}', [InquiryController::class, 'updateProduct'])->name('inquiry-product.update');
        Route::post('inquiry-product/update-decisions-batch', [InquiryController::class, 'updateDecisionsBatch'])->name('inquiry-product.update-decisions-batch');
        Route::delete('inquiry-product/{id}', [InquiryController::class, 'deleteProduct'])->name('inquiry-product.delete');
        Route::get('inquiry-product/{productId}/chats', [\App\Http\Controllers\Management\InquiryProductChatController::class, 'index'])->name('inquiry-product.chats.index');
        Route::post('inquiry-product/{productId}/chats', [\App\Http\Controllers\Management\InquiryProductChatController::class, 'store'])->name('inquiry-product.chats.store');
        Route::get('inquiry-product-chat/download/{chatId}', [\App\Http\Controllers\Management\InquiryProductChatController::class, 'download'])->name('inquiry-product.chats.download');
        Route::get('inquiry-product-chat/file/{chatId}', [\App\Http\Controllers\Management\InquiryProductChatController::class, 'showFile'])->name('inquiry-product.chats.show-file');
        Route::delete('inquiry-product-chat/{chatId}', [\App\Http\Controllers\Management\InquiryProductChatController::class, 'destroy'])->name('inquiry-product.chats.destroy');
        Route::post('inquiry-product/reorder-all', [InquiryController::class, 'reorderAll'])->name('inquiry-product.reorder-all');
        Route::post('inquiry-product/{id}/reorder', [InquiryController::class, 'reorderProduct'])->name('inquiry-product.reorder');
        Route::post('inquiry/{inquiryId}/product', [InquiryController::class, 'addProduct'])->name('inquiry.add-product');
        Route::post('inquiry/{inquiry}/cancel', [InquiryController::class, 'cancel'])->name('inquiry.cancel');
        Route::post('inquiry/{inquiry}/close', [InquiryController::class, 'close'])->name('inquiry.close');
        Route::resource('inquiry', InquiryController::class)->parameters(['inquiry' => 'id']);
    });

    // Work Order Routes
    // Work Order Public API Routes (accessible by any authenticated user on work order screen)
    Route::get('work-order/{id}/ajax-details', [WorkOrderController::class, 'apiGetDetails'])->name('work-order.api-details');
    Route::get('work-order-global-progress', [WorkOrderController::class, 'apiGetGlobalProgress'])->name('work-order.api-global-progress');
    Route::post('work-order/{id}/resend-email', [WorkOrderController::class, 'resendEmail'])->name('work-order.resend-email');

    // Work Order Approval Inbox (accessible by users with approval menu permission)
    Route::middleware('check.menu:management.work-order.approval-inbox')->group(function () {
        Route::get('work-order-approval', [WorkOrderController::class, 'approvalInbox'])->name('work-order.approval-inbox');
        Route::get('work-order/{id}/review', [WorkOrderController::class, 'reviewPage'])->name('work-order.review');
        Route::post('work-order/{id}/approve', [WorkOrderController::class, 'approve'])->name('work-order.approve');
        Route::post('work-order/{id}/reject', [WorkOrderController::class, 'reject'])->name('work-order.reject');
    });

    // Shared Work Order Actions (Accessible across all WO types: WO 1, WO 2 Tooling, WO 2 Add Process, WO 2 Fastener)
    Route::post('work-order/{id}/submit', [WorkOrderController::class, 'submit'])->name('work-order.submit');
    Route::post('work-order/{id}/revise', [WorkOrderController::class, 'revise'])->name('work-order.revise');
    Route::post('work-order/{id}/progress', [WorkOrderController::class, 'updateProgress'])->name('work-order.update-progress');
    Route::post('process-checklist', [WorkOrderController::class, 'storeProcess'])->name('process-checklist.store');
    Route::post('process-checklist/{id}/update', [WorkOrderController::class, 'updateProcess'])->name('process-checklist.update');
    Route::post('process-checklist/{id}/delete', [WorkOrderController::class, 'destroyProcess'])->name('process-checklist.destroy');

    // 1. WO 1 Tech Feasibility Routes
    Route::middleware('check.menu:management.work-order.index')->group(function () {
        Route::match(['get', 'post'], 'work-order/create', [WorkOrderController::class, 'create'])->name('work-order.create');
        Route::resource('work-order', WorkOrderController::class)->parameters(['work-order' => 'id'])->except(['create']);
    });

    // 2. WO 2 Tooling Cost Routes
    Route::middleware('check.menu:management.work-order-tooling.index')->group(function () {
        Route::get('work-order-tooling/{id}/quotation', [WorkOrderToolingController::class, 'exportQuotation'])->name('work-order-tooling.quotation');
        Route::match(['get', 'post'], 'work-order-tooling/create', [WorkOrderToolingController::class, 'create'])->name('work-order-tooling.create');
        Route::resource('work-order-tooling', WorkOrderToolingController::class)->parameters(['work-order-tooling' => 'id'])->except(['create']);

        // Tooling Quotation Routes
        Route::resource('tooling-quotation', \App\Http\Controllers\FeasibilityStudy\ToolingQuotationCompareController::class)->parameters(['tooling-quotation' => 'id']);
        Route::post('tooling-quotation/import', [\App\Http\Controllers\FeasibilityStudy\ToolingQuotationCompareController::class, 'import'])->name('tooling-quotation.import');
    });

    // 3. WO 2 Additional Process Routes
    Route::middleware('check.menu:management.work-order-add-process.index')->group(function () {
        Route::match(['get', 'post'], 'work-order-add-process/create', [WorkOrderAddProcessController::class, 'create'])->name('work-order-add-process.create');
        Route::resource('work-order-add-process', WorkOrderAddProcessController::class)->parameters(['work-order-add-process' => 'id'])->except(['create']);
    });

    // 4. WO 2 Fastener / Standard Part Routes
    Route::middleware('check.menu:management.work-order-fastener.index')->group(function () {
        Route::match(['get', 'post'], 'work-order-fastener/create', [WorkOrderFastenerController::class, 'create'])->name('work-order-fastener.create');
        Route::resource('work-order-fastener', WorkOrderFastenerController::class)->parameters(['work-order-fastener' => 'id'])->except(['create']);
    });

    // 5. WO 2 Raw Material Specification Routes
    Route::middleware('check.menu:management.work-order-material.index')->group(function () {
        Route::match(['get', 'post'], 'work-order-material/create', [\App\Http\Controllers\FeasibilityStudy\WorkOrderMaterialController::class, 'create'])->name('work-order-material.create');
        Route::resource('work-order-material', \App\Http\Controllers\FeasibilityStudy\WorkOrderMaterialController::class)->parameters(['work-order-material' => 'id'])->except(['create']);
    });

    // Assessment Configuration Routes
    Route::middleware('check.menu:management.assessment-config.index')->group(function () {
        Route::get('assessment-config', [AssessmentConfigController::class, 'index'])->name('assessment-config.index');
        Route::post('assessment-config/category', [AssessmentConfigController::class, 'storeCategory'])->name('assessment-config.category.store');
        Route::post('assessment-config/category/{id}/update', [AssessmentConfigController::class, 'updateCategory'])->name('assessment-config.category.update');
        Route::post('assessment-config/option', [AssessmentConfigController::class, 'storeOption'])->name('assessment-config.option.store');
        Route::post('assessment-config/option/{id}/update', [AssessmentConfigController::class, 'updateOption'])->name('assessment-config.option.update');
        Route::post('assessment-config/ranking', [AssessmentConfigController::class, 'storeRanking'])->name('assessment-config.ranking.store');
        Route::post('assessment-config/ranking/{id}/update', [AssessmentConfigController::class, 'updateRanking'])->name('assessment-config.ranking.update');
    });

    // Calendar & Holiday Routes
    Route::middleware('check.menu:management.calendar.index')->group(function () {
        Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::get('calendar/events', [CalendarController::class, 'getEvents'])->name('calendar.events');
        Route::get('calendar/holidays', [CalendarController::class, 'getHolidays'])->name('calendar.holidays');
        Route::post('calendar', [CalendarController::class, 'store'])->name('calendar.store');
        Route::put('calendar/{id}', [CalendarController::class, 'update'])->name('calendar.update');
        Route::delete('calendar/{id}', [CalendarController::class, 'destroy'])->name('calendar.destroy');
    });

    // Approval Rules Configuration Routes
    Route::middleware('check.menu:management.approval-config.index')->group(function () {
        Route::get('approval-config', [ApprovalConfigController::class, 'index'])->name('approval-config.index');
        Route::post('approval-config', [ApprovalConfigController::class, 'store'])->name('approval-config.store');
        Route::post('approval-config/{id}/update', [ApprovalConfigController::class, 'update'])->name('approval-config.update');
        Route::post('approval-config/{id}/delete', [ApprovalConfigController::class, 'destroy'])->name('approval-config.destroy');
    });

    // EBD (Engineering Breakdown) Routes
    Route::get('ebd', [EbdController::class, 'index'])->name('ebd.index');
    Route::get('ebd/{id}', [EbdController::class, 'show'])->name('ebd.show');
    Route::post('ebd/import', [EbdController::class, 'import'])->name('ebd.import');
    Route::post('ebd/{id}/update', [EbdController::class, 'updateHeader'])->name('ebd.update');
    Route::post('ebd/{id}/create-revision', [EbdController::class, 'createRevision'])->name('ebd.create-revision');
    Route::post('ebd/{id}/delete', [EbdController::class, 'destroy'])->name('ebd.destroy');

    // EBD Items CRUD
    Route::post('ebd/{ebdHeaderId}/items', [EbdController::class, 'storeItem'])->name('ebd.store-item');
    Route::post('ebd/items/{itemId}/update', [EbdController::class, 'updateItem'])->name('ebd.update-item');
    Route::post('ebd/items/{itemId}/delete', [EbdController::class, 'destroyItem'])->name('ebd.destroy-item');

    // EBD Tooling Process CRUD
    Route::post('ebd/items/{itemId}/tooling', [EbdController::class, 'storeToolingProcess'])->name('ebd.store-tooling');
    Route::post('ebd/tooling/{id}/update', [EbdController::class, 'updateToolingProcess'])->name('ebd.update-tooling');
    Route::post('ebd/tooling/{id}/delete', [EbdController::class, 'destroyToolingProcess'])->name('ebd.destroy-tooling');

    // EBD Additional Process CRUD
    Route::post('ebd/items/{itemId}/addprocess', [EbdController::class, 'storeAddProcess'])->name('ebd.store-addprocess');
    Route::post('ebd/addprocess/{id}/update', [EbdController::class, 'updateAddProcess'])->name('ebd.update-addprocess');
    Route::post('ebd/addprocess/{id}/delete', [EbdController::class, 'destroyAddProcess'])->name('ebd.destroy-addprocess');

    // EBD Request Routes (Sales/Marketing -> Engineering Revision Request)
    Route::get('ebd-request', [EbdRequestController::class, 'index'])->name('ebd-request.index');
    Route::post('ebd-request', [EbdRequestController::class, 'store'])->name('ebd-request.store');
    Route::post('ebd-request/{id}/update', [EbdRequestController::class, 'update'])->name('ebd-request.update');
    Route::get('ebd-request/get-ebd-by-wo/{woId?}', [EbdRequestController::class, 'getEbdByWo'])->name('ebd-request.get-ebd');
    Route::post('ebd-request/{id}/update-status', [EbdRequestController::class, 'updateStatus'])->name('ebd-request.update-status');
    Route::post('ebd-request/{id}/delete', [EbdRequestController::class, 'destroy'])->name('ebd-request.destroy');

    // Dynamic Excel Template Engine & Visual Mapping Studio Routes
    Route::get('excel-templates', [\App\Http\Controllers\Admin\ExcelTemplateController::class, 'index'])->name('excel-templates.index');
    Route::post('excel-templates', [\App\Http\Controllers\Admin\ExcelTemplateController::class, 'store'])->name('excel-templates.store');
    Route::post('excel-templates/{id}/update', [\App\Http\Controllers\Admin\ExcelTemplateController::class, 'update'])->name('excel-templates.update');
    Route::post('excel-templates/{id}/delete', [\App\Http\Controllers\Admin\ExcelTemplateController::class, 'destroy'])->name('excel-templates.destroy');
    Route::post('excel-templates/{id}/duplicate', [\App\Http\Controllers\Admin\ExcelTemplateController::class, 'duplicate'])->name('excel-templates.duplicate');
    Route::get('excel-templates/{id}/builder', [\App\Http\Controllers\Admin\ExcelTemplateController::class, 'builder'])->name('excel-templates.builder');
    Route::get('excel-templates/{id}/preview', [\App\Http\Controllers\Admin\ExcelTemplateController::class, 'preview'])->name('excel-templates.preview');
    Route::post('excel-templates/{id}/mapping', [\App\Http\Controllers\Admin\ExcelTemplateController::class, 'saveMapping'])->name('excel-templates.save-mapping');
    Route::post('excel-templates/{id}/toggle-status', [\App\Http\Controllers\Admin\ExcelTemplateController::class, 'toggleStatus'])->name('excel-templates.toggle-status');

    Route::get('system-fields', [\App\Http\Controllers\Admin\SystemFieldController::class, 'index'])->name('system-fields.index');
    Route::post('system-fields', [\App\Http\Controllers\Admin\SystemFieldController::class, 'store'])->name('system-fields.store');

    // Manufacturing Process Cost Master Data Routes
    Route::middleware('check.menu:management.mfg-process-cost.index')->group(function () {
        Route::get('mfg-process-cost', [MfgProcessCostController::class, 'index'])->name('mfg-process-cost.index');
        Route::get('mfg-process-cost/data', [MfgProcessCostController::class, 'data'])->name('mfg-process-cost.data');
        Route::get('mfg-process-cost/export', [MfgProcessCostController::class, 'export'])->name('mfg-process-cost.export');
        Route::post('mfg-process-cost', [MfgProcessCostController::class, 'store'])->name('mfg-process-cost.store');
        Route::put('mfg-process-cost/{id}', [MfgProcessCostController::class, 'update'])->name('mfg-process-cost.update');
        Route::delete('mfg-process-cost/{id}', [MfgProcessCostController::class, 'destroy'])->name('mfg-process-cost.destroy');
    });

    // Manufacturing Stamping Process Cost Master Data Routes
    Route::middleware('check.menu:management.mfg-process-stp-cost.index')->group(function () {
        Route::get('mfg-process-stp-cost', [MfgProcessStpCostController::class, 'index'])->name('mfg-process-stp-cost.index');
        Route::get('mfg-process-stp-cost/data', [MfgProcessStpCostController::class, 'data'])->name('mfg-process-stp-cost.data');
        Route::get('mfg-process-stp-cost/export', [MfgProcessStpCostController::class, 'export'])->name('mfg-process-stp-cost.export');
        Route::post('mfg-process-stp-cost', [MfgProcessStpCostController::class, 'store'])->name('mfg-process-stp-cost.store');
        Route::put('mfg-process-stp-cost/{id}', [MfgProcessStpCostController::class, 'update'])->name('mfg-process-stp-cost.update');
        Route::delete('mfg-process-stp-cost/{id}', [MfgProcessStpCostController::class, 'destroy'])->name('mfg-process-stp-cost.destroy');
    });

    // Material Cost Master Data Routes
    Route::middleware('check.menu:management.material-cost.index')->group(function () {
        Route::get('material-cost', [MaterialCostController::class, 'index'])->name('material-cost.index');
        Route::get('material-cost/data', [MaterialCostController::class, 'data'])->name('material-cost.data');
        Route::get('material-cost/export', [MaterialCostController::class, 'export'])->name('material-cost.export');
        Route::post('material-cost', [MaterialCostController::class, 'store'])->name('material-cost.store');
        Route::put('material-cost/{id}', [MaterialCostController::class, 'update'])->name('material-cost.update');
        Route::delete('material-cost/{id}', [MaterialCostController::class, 'destroy'])->name('material-cost.destroy');
    });

    // Cost Policy & Markup Master Data Routes
    Route::middleware('check.menu:management.cost-policy.index')->group(function () {
        Route::get('cost-policy', [CustomerCostPolicyController::class, 'index'])->name('cost-policy.index');
        Route::get('cost-policy/data', [CustomerCostPolicyController::class, 'data'])->name('cost-policy.data');
        Route::get('cost-policy/export', [CustomerCostPolicyController::class, 'export'])->name('cost-policy.export');
        Route::post('cost-policy', [CustomerCostPolicyController::class, 'store'])->name('cost-policy.store');
        Route::put('cost-policy/{id}', [CustomerCostPolicyController::class, 'update'])->name('cost-policy.update');
        Route::delete('cost-policy/{id}', [CustomerCostPolicyController::class, 'destroy'])->name('cost-policy.destroy');
    });

    // Product Cost Comparison Routes (Eng vs Sales Matrix)
    Route::middleware('check.menu:management.product-cost-comparison.index')->group(function () {
        Route::get('product-cost-comparison', [ProductCostComparisonController::class, 'index'])->name('product-cost-comparison.index');
        Route::get('product-cost-comparison/models', [ProductCostComparisonController::class, 'getModelsByCustomer'])->name('product-cost-comparison.models');
        Route::get('product-cost-comparison/ebds', [ProductCostComparisonController::class, 'getEbdsByModel'])->name('product-cost-comparison.ebds');
        Route::get('product-cost-comparison/export', [ProductCostComparisonController::class, 'export'])->name('product-cost-comparison.export');
        Route::get('product-cost-comparison/{id}', [ProductCostComparisonController::class, 'show'])->name('product-cost-comparison.show');
        Route::get('product-cost-comparison/{id}/quotation', [ProductCostComparisonController::class, 'exportQuotation'])->name('product-cost-comparison.quotation');
        Route::get('product-cost-comparison/{id}/items-data', [ProductCostComparisonController::class, 'itemsData'])->name('product-cost-comparison.items-data');
        Route::post('product-cost-comparison/import', [ProductCostComparisonController::class, 'importQuotation'])->name('product-cost-comparison.import');
    });
});


