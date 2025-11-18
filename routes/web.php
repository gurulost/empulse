<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Request;
use App\Models\User;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FacebookController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\ChiefController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\TeamleadController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\CompanyMainPageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ContuctUsController;
use App\Http\Controllers\WorkfitAdminController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\SurveyManagementController;
use App\Http\Controllers\SurveyWaveController;
use App\Http\Controllers\DashboardAnalyticsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();
Route::get('/', [WelcomeController::class, 'welcome'])->name('welcome');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/google', [SocialController::class, 'googleRedirect'])->name('auth.google');
Route::get('/auth/google/callback', [SocialController::class, 'loginWithGoogle']);
Route::get('/facebook', [FacebookController::class, 'facebookRedirect'])->name('auth.facebook');
Route::get('/facebook/callback', [FacebookController::class, 'facebookLogin']);

Route::get('/survey/{token}', [SurveyController::class, 'show'])->name('survey.take');
Route::get('/survey/{token}/definition', [SurveyController::class, 'definition'])->name('survey.definition');
Route::post('/survey/{token}/autosave', [SurveyController::class, 'autosave'])->name('survey.autosave');
Route::post('/survey/{token}', [SurveyController::class, 'submit'])->name('survey.submit');

Route::group(['middleware' => 'auth'], function () {
    Route::group(['prefix' => 'home', 'middleware' => 'admin'], function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::post('/updatePassword/{email}', [HomeController::class, 'updatePassword']);
        Route::get('/response_error', [PaymentController::class, 'responses_error']);
});

// Stripe webhook endpoint (Cashier)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

    Route::group(['prefix' => 'users', 'middleware' => 'admin'], function () {
        Route::get('/', [AdminController::class, 'upload_coworkers'])->name("company_staff");
        Route::post('/', [AdminController::class, 'add_worker']);
        Route::post('/import', [UserController::class, 'importUsers']);
        Route::get('/export/{role}', [UserController::class, 'exportTable']);
        Route::get('/delete/{email}', [AdminController::class, 'delete'])->middleware('tenant.email');
        Route::put('/{email}', [AdminController::class, 'updateUser'])->name('udpateUser')->middleware('tenant.email');
        Route::post('/manager_status/{email}', [AdminController::class, 'manager_status'])->middleware('tenant.email');
        Route::post('/teamlead_status/{email}', [AdminController::class, 'teamlead_status'])->middleware('tenant.email');
        Route::post('/chief_status/{email}', [AdminController::class, 'chief_status'])->middleware('tenant.email');
        Route::post('/employee_status/{email}', [AdminController::class, 'employee_status'])->middleware('tenant.email');
        Route::get('/list', [AdminController::class, 'usersPagination'])->name("company_staff_list");;
        Route::post("/changeName_chief/{name}", [AdminController::class, "changeName_chief"]);
        Route::post("/changeEmail_chief/{email}", [AdminController::class, "changeEmail_chief"]);
        Route::put('/coworker/update/{param}/{currenty}/{new}', [HomeController::class, 'update_coworker_name'])->name('update_coworker_name');
        Route::put('/coworker/{email}/department/{department}', [HomeController::class, 'update_coworker_department'])->name('update_coworker_department');
    });

    Route::group(['prefix' => 'contuctUs', 'middleware' => 'admin'], function() {
        Route::get('/', [ContuctUsController::class, 'index'])->name('contuctUs');
        Route::post('/', [ContuctUsController::class, 'sendForm']);
        Route::get('/response', [ContuctUsController::class, 'response']);
    });

    Route::group(['prefix' => 'departments', 'middleware' => 'admin'], function () {
        Route::get('/', [AdminController::class, 'departments'])->name("departments");
        Route::get('/list', [AdminController::class, 'departments_list']);
        Route::get('/delete/{title}', [AdminController::class, 'deleteDepartment']);
        Route::post('/', [AdminController::class, 'addDepartment']);
        Route::post('/update/{title}', [AdminController::class, 'updateDepartment']);
    });

    Route::group(['prefix' => 'payment', 'middleware' => 'admin'], function() {
        Route::get('/', [PaymentController::class, 'payment'])->name('payment')->middleware('payment');
        Route::get('/payment-success', [PaymentController::class, 'payment_success'])->name('payment-success');
        Route::get('/error', [PaymentController::class, 'payment_error'])->name('payment_error');
    });

    // Stripe subscription (Laravel Cashier) routes
    Route::group(['middleware' => 'admin'], function () {
        Route::get('/plans', [PlanController::class, 'stripePay'])->name('plans.index');
        Route::get('/plans/{plan}', [PlanController::class, 'show'])->name('plans.show');
        Route::post('/subscription', [PlanController::class, 'subscription'])->name('subscription.create');

        // Account & Billing
        Route::prefix('/account')->group(function () {
            Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
            Route::post('/billing/payment-method', [BillingController::class, 'updatePaymentMethod'])->name('billing.payment_method');
            Route::post('/billing/cancel', [BillingController::class, 'cancel'])->name('billing.cancel');
            Route::post('/billing/resume', [BillingController::class, 'resume'])->name('billing.resume');
            Route::post('/billing/portal', [BillingController::class, 'portal'])->name('billing.portal');
        });
    });

    Route::group(['middleware' => 'admin'], function() {
        Route::get('/profile', [UserController::class, 'profile'])->name('profile');
        Route::post("/profile/edit_password", [UserController::class, 'editPassword'])->name("edit-password");
        Route::get('/add/avatar', [UserController::class, 'addAvatar'])->name('add.avatar');
        Route::post('/store/avatar', [UserController::class, 'storeAvatar'])->name('store.avatar');
        Route::get('/delete/avatar/{id}', [UserController::class, 'deleteAvatar'])->name('delete.avatar');
        Route::get('/surveys/manage', [SurveyManagementController::class, 'index'])->name('surveys.manage');
        Route::get('/survey-waves', [SurveyWaveController::class, 'index'])->name('survey-waves.index');
        Route::post('/survey-waves', [SurveyWaveController::class, 'store'])->name('survey-waves.store');
    });
    Route::group(['middleware' => 'workfit_admin'], function () {
        Route::prefix('/admin')->name('admin.')->group(function () {
            Route::prefix('/company')->name('company.')->group(function () {
                Route::get('list', [WorkfitAdminController::class, 'getCompanyList'])->name('list');
                Route::get('{id}', [WorkfitAdminController::class, 'getCompany'])->name('item');
            });

            Route::prefix('/subscription')->name('subscription.')->group(function () {
                Route::get('list', [WorkfitAdminController::class, 'getSubscriptionList'])->name('list');
            });

            Route::prefix('/users')->name('users.')->group(function () {
                Route::get('delete/{id}', [WorkfitAdminController::class, 'deleteUser'])->name('delete');
                Route::get('list', [WorkfitAdminController::class, 'getUsersList'])->name('list');
            });
        });
    });

    Route::get('/dashboard/analytics', DashboardAnalyticsController::class)
        ->name('dashboard.analytics');
});
