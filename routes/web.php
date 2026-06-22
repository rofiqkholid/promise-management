<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Management\InquiryController;
use App\Http\Controllers\Management\AssessmentConfigController;
use App\Http\Controllers\Management\WorkOrderController;
use App\Http\Controllers\Management\ApprovalRuleController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


Route::get('/login', function () {
    if (request()->has('redirect')) {
        session()->put('url.intended', request()->get('redirect'));
    }
    return redirect(env('PORTAL_LOGIN_URL', 'http://localhost:8080/login'));
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
    return redirect(env('PORTAL_LOGIN_URL', 'http://localhost:8080/login'));
})->name('logout');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->prefix('management')->name('management.')->group(function () {
    // Inquiry Routes
    Route::middleware('check.menu:2')->group(function () {
        Route::post('inquiry/{inquiry}/import', [InquiryController::class, 'import'])->name('inquiry.import');
        Route::get('inquiry-template/download', [InquiryController::class, 'downloadTemplate'])->name('inquiry.download-template');
        Route::post('inquiry/{inquiry}/parse-excel', [InquiryController::class, 'parseExcel'])->name('inquiry.parse-excel');
        Route::post('inquiry/{inquiry}/finalize', [InquiryController::class, 'finalize'])->name('inquiry.finalize');
        Route::get('inquiry-product/{id}/assess', [InquiryController::class, 'assessPage'])->name('inquiry-product.assess-page');
        Route::post('inquiry-product/{id}/assess', [InquiryController::class, 'assessProduct'])->name('inquiry-product.assess');
        Route::patch('inquiry-product/{id}', [InquiryController::class, 'updateProduct'])->name('inquiry-product.update');
        Route::post('inquiry-product/{id}/reorder', [InquiryController::class, 'reorderProduct'])->name('inquiry-product.reorder');
        Route::post('inquiry/{inquiryId}/product', [InquiryController::class, 'addProduct'])->name('inquiry.add-product');
        Route::post('inquiry/{inquiry}/cancel', [InquiryController::class, 'cancel'])->name('inquiry.cancel');
        Route::post('inquiry/{inquiry}/close', [InquiryController::class, 'close'])->name('inquiry.close');
        Route::resource('inquiry', InquiryController::class)->parameters(['inquiry' => 'id']);
    });

    // Work Order Routes
    Route::middleware('check.menu:3')->group(function () {
        Route::post('work-order/{id}/submit', [WorkOrderController::class, 'submit'])->name('work-order.submit');
        Route::post('work-order/{id}/approve', [WorkOrderController::class, 'approve'])->name('work-order.approve');
        Route::post('work-order/{id}/reject', [WorkOrderController::class, 'reject'])->name('work-order.reject');
        Route::resource('work-order', WorkOrderController::class)->parameters(['work-order' => 'id']);
    });

    // Assessment Configuration Routes
    Route::middleware('check.menu:5')->group(function () {
        Route::get('assessment-config', [AssessmentConfigController::class, 'index'])->name('assessment-config.index');
        Route::post('assessment-config/category', [AssessmentConfigController::class, 'storeCategory'])->name('assessment-config.category.store');
        Route::post('assessment-config/category/{id}/update', [AssessmentConfigController::class, 'updateCategory'])->name('assessment-config.category.update');
        Route::post('assessment-config/option', [AssessmentConfigController::class, 'storeOption'])->name('assessment-config.option.store');
        Route::post('assessment-config/option/{id}/update', [AssessmentConfigController::class, 'updateOption'])->name('assessment-config.option.update');
        Route::post('assessment-config/ranking', [AssessmentConfigController::class, 'storeRanking'])->name('assessment-config.ranking.store');
        Route::post('assessment-config/ranking/{id}/update', [AssessmentConfigController::class, 'updateRanking'])->name('assessment-config.ranking.update');
    });

    // Approval Rules Configuration Routes
    Route::middleware('check.menu:6')->group(function () {
        Route::get('approval-config', [ApprovalRuleController::class, 'index'])->name('approval-config.index');
        Route::post('approval-config', [ApprovalRuleController::class, 'store'])->name('approval-config.store');
        Route::post('approval-config/{id}/update', [ApprovalRuleController::class, 'update'])->name('approval-config.update');
        Route::post('approval-config/{id}/delete', [ApprovalRuleController::class, 'destroy'])->name('approval-config.destroy');
    });
});

