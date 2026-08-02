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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


Route::get('/login', function () {
    if (request()->has('redirect')) {
        session()->put('url.intended', request()->get('redirect'));
    }
    return redirect(env('PORTAL_LOGIN_URL', 'https://promise.summitadyawinsa.co.id/login'));
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
    return redirect(env('PORTAL_LOGIN_URL', 'https://promise.summitadyawinsa.co.id/login'));
})->name('logout');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->prefix('management')->name('management.')->group(function () {
    Route::get('ajax/users', [WorkOrderController::class, 'apiGetUsers'])->name('api.users');
    Route::get('ajax/suppliers', [\App\Http\Controllers\FeasibilityStudy\ToolingQuotationCompareController::class, 'apiGetSuppliers'])->name('api.suppliers');
    Route::get('ajax/exchange-rate', [\App\Http\Controllers\FeasibilityStudy\ToolingQuotationCompareController::class, 'apiGetExchangeRate'])->name('api.exchange-rate');
    Route::get('ajax/processes', [WorkOrderController::class, 'apiGetProcesses'])->name('api.processes');
    Route::get('ajax/approval-rules', [ApprovalConfigController::class, 'apiGetRules'])->name('api.approval-rules');

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

    // Calendar & Holiday Routes
    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('calendar/events', [CalendarController::class, 'getEvents'])->name('calendar.events');
    Route::post('calendar/events', [CalendarController::class, 'store'])->name('calendar.store');
    Route::match(['patch', 'post'], 'calendar/events/{id}', [CalendarController::class, 'update'])->name('calendar.update');
    Route::match(['delete', 'post'], 'calendar/events/{id}/delete', [CalendarController::class, 'destroy'])->name('calendar.destroy');
    Route::get('calendar/holidays', [CalendarController::class, 'getHolidays'])->name('calendar.holidays');
});

