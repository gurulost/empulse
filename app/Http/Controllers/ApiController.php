<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SurveyAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiController extends Controller
{
    protected SurveyAnalyticsService $analyticsService;

    public function __construct(SurveyAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function qualtrics(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized']);
        }

        $companyId = Auth::user()->company_id;
        $dataset = $this->analyticsService->datasetForCompany($companyId);

        $model = new User();
        $qualtrics = $model->qualtricsFunc(Auth::user()->name, Auth::user()->email, Auth::user()->role, Auth::user()->password, Auth::user()->company_title, $dataset);

        return response()->json([
            'status' => 200,
            'message' => $qualtrics,
        ]);
    }
}
