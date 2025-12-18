<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\SocialController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FacebookController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\ChiefController;

use App\Http\Controllers\TeamleadController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\CompanyMainPageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\WorkfitAdminController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\SurveyManagementController;
use App\Http\Controllers\SurveyWaveController;
use App\Http\Controllers\SurveyBuilderController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\DashboardAnalyticsController;
use App\Http\Controllers\AnalyticsApiController;
use App\Http\Controllers\EmployeeDashboardController;

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

Route::get('/contact', [ContactUsController::class, 'index'])->name('contact.form');
Route::post('/contact', [ContactUsController::class, 'sendForm'])->name('contact.send');
Route::get('/contact/response', [ContactUsController::class, 'response'])->name('contact.response');

Route::redirect('/contuctUs', '/contact');
Route::redirect('/contuctUs/response', '/contact/response');

Route::get('/google', [SocialController::class, 'googleRedirect'])->name('auth.google');
Route::get('/auth/google/callback', [SocialController::class, 'loginWithGoogle']);
Route::get('/facebook', [FacebookController::class, 'facebookRedirect'])->name('auth.facebook');
Route::get('/facebook/callback', [FacebookController::class, 'facebookLogin']);

Route::get('/survey/{token}', [SurveyController::class, 'show'])->name('survey.take');
Route::get('/survey/{token}/definition', [SurveyController::class, 'definition'])->name('survey.definition');
// Reports
Route::get('/reports', [ReportController::class, 'index'])->middleware('auth')->name('reports.index');
Route::get('/reports/trends', [App\Http\Controllers\ReportsApiController::class, 'getTrends'])->middleware('auth');
Route::get('/reports/comparison', [App\Http\Controllers\ReportsApiController::class, 'getComparison'])->middleware('auth')->name('reports.comparison');

Route::post('/survey/{token}/autosave', [SurveyController::class, 'autosave'])->name('survey.autosave');
Route::post('/survey/{token}', [SurveyController::class, 'submit'])->name('survey.submit');

Route::group(['middleware' => 'auth'], function () {
    Route::get('/employee', [EmployeeDashboardController::class, 'index'])->name('employee.dashboard');

    Route::group(['prefix' => 'home', 'middleware' => 'admin'], function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::post('/updatePassword/{email}', [HomeController::class, 'updatePassword']);
        Route::get('/response_error', [PaymentController::class, 'responses_error']);
    });

    // Stripe webhook endpoint (Cashier)
    Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

    // Team Management (Vue Dashboard)
    Route::get('/team/manage', [TeamController::class, 'index'])->middleware('admin')->name('team.manage');

    // Team Management JSON API
    Route::group(['prefix' => 'team/api', 'middleware' => 'admin'], function () {
        // Members
        Route::get('/members', [TeamController::class, 'getMembers']);
        Route::post('/members', [TeamController::class, 'addMember']);
        Route::put('/members/{email}', [TeamController::class, 'updateMember']);
        Route::delete('/members/{email}', [TeamController::class, 'deleteMember']);
        Route::post('/members/import', [TeamController::class, 'importUsers']);
        
        // Departments
        Route::get('/departments', [TeamController::class, 'getDepartments']);
        Route::post('/departments', [TeamController::class, 'addDepartment']);
        Route::put('/departments/{title}', [TeamController::class, 'updateDepartment']);
        Route::delete('/departments/{title}', [TeamController::class, 'deleteDepartment']);
    });

    Route::group(['prefix' => 'users', 'middleware' => 'admin'], function () {
        Route::get('/export/{role}', [UserController::class, 'exportTable']);
    });

    Route::group(['prefix' => 'users'], function () {
        Route::get('/delete/{email}', [TeamController::class, 'deleteMemberLegacy'])->name('users.delete');
        Route::get('/list', [TeamController::class, 'getMembersLegacy'])->name('users.list');
        Route::post('/import', [TeamController::class, 'importUsers'])->name('users.import');
    });

    Route::group(['prefix' => 'departments'], function () {
        Route::get('/list', [TeamController::class, 'getDepartmentsLegacy'])->name('departments.list');
        Route::post('/', [TeamController::class, 'addDepartmentLegacy'])->name('departments.store');
        Route::get('/delete/{title}', [TeamController::class, 'deleteDepartmentLegacy'])->name('departments.delete');
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
        Route::post('/survey-waves/{wave}/status', [SurveyWaveController::class, 'updateStatus'])->name('survey-waves.status');
        Route::post('/survey-waves/{wave}/dispatch', [SurveyWaveController::class, 'dispatchWave'])->name('survey-waves.dispatch');
    });
    Route::group(['middleware' => 'workfit_admin'], function () {
        Route::prefix('/admin')->name('admin.')->group(function () {
            Route::get('/', [WorkfitAdminController::class, 'index'])->name('dashboard');
            
            Route::prefix('/api')->group(function () {
                Route::get('/companies', [WorkfitAdminController::class, 'getCompanies']);
                Route::get('/users', [WorkfitAdminController::class, 'getUsers']);
                Route::delete('/users/{id}', [WorkfitAdminController::class, 'deleteUser']);
                Route::post('/users/{id}/impersonate', [WorkfitAdminController::class, 'impersonate']);
            });

            Route::prefix('/builder')->name('builder.')->group(function () {
                Route::get('/', [SurveyBuilderController::class, 'index'])->name('index');
                Route::get('/structure/{versionId}', [SurveyBuilderController::class, 'getStructure']);
                Route::post('/draft/{surveyId}', [SurveyBuilderController::class, 'createDraft']);
                Route::post('/publish/{versionId}', [SurveyBuilderController::class, 'publishVersion']);
                Route::post('/item/{itemId}', [SurveyBuilderController::class, 'updateItem']);
                Route::post('/reorder', [SurveyBuilderController::class, 'reorderItems']);
            });

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

    Route::get('/analytics/api/dashboard', [AnalyticsApiController::class, 'index'])
        ->name('analytics.api.dashboard');
});
