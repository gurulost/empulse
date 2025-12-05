<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyItem;
use App\Models\SurveyVersion;
use App\Services\SurveyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SurveyBuilderController extends Controller
{
    protected SurveyService $surveyService;

    public function __construct(SurveyService $surveyService)
    {
        $this->middleware(['auth', 'workfit_admin']);
        $this->surveyService = $surveyService;
    }

    public function index()
    {
        // Default to latest version for now
        $version = SurveyVersion::orderByDesc('id')->firstOrFail();
        $survey = Survey::firstOrFail(); // Simplified for single survey app

        return view('surveys.builder', [
            'versionId' => $version->id,
            'surveyId' => $survey->id,
        ]);
    }

    public function getStructure($versionId)
    {
        $version = SurveyVersion::with([
            'pages' => function ($q) { $q->orderBy('sort_order'); },
            'pages.sections' => function ($q) { $q->orderBy('sort_order'); },
            'pages.items' => function ($q) { $q->orderBy('sort_order'); },
            'pages.sections.items' => function ($q) { $q->orderBy('sort_order'); },
            'pages.items.options',
            'pages.sections.items.options',
        ])->findOrFail($versionId);

        return response()->json($version);
    }

    public function createDraft(Request $request, $surveyId)
    {
        $survey = Survey::findOrFail($surveyId);
        // Find the latest version associated with this survey (assuming survey_id link or instrument_id convention)
        // For now, we'll assume the survey has a 'metadata' field or similar that might hold the instrument_id, 
        // or we just look for the latest version that matches this survey's "type".
        // Given the previous hardcoding, let's try to find *any* version for this survey if possible, 
        // or fallback to the hardcoded 'eng_v1' if it's a specific system constant.
        
        // Better approach: Find the latest version for this survey_id directly if the column exists, 
        // otherwise fallback to the known instrument_id.
        $latestVersion = SurveyVersion::where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if (!$latestVersion) {
             $latestVersion = SurveyVersion::orderByDesc('id')->first();
        }

        if (!$latestVersion) {
            return response()->json(['message' => 'No base version found'], 404);
        }

        $draft = $this->surveyService->cloneVersion($latestVersion);

        return response()->json(['draft_id' => $draft->id]);
    }

    public function publishVersion($versionId)
    {
        $version = SurveyVersion::findOrFail($versionId);
        
        if ($version->is_active) {
            return response()->json(['message' => 'Already active'], 422);
        }

        $this->surveyService->publishVersion($version);

        return response()->json(['status' => 'published']);
    }

    public function updateItem(Request $request, $itemId)
    {
        $item = SurveyItem::findOrFail($itemId);
        
        if ($item->version->is_active) {
            return response()->json(['message' => 'Cannot edit active version'], 403);
        }

        $item->update($request->only([
            'question', 'type', 'scale_config', 'metadata', 'display_logic'
        ]));

        if ($request->has('options')) {
            $item->options()->delete();
            foreach ($request->input('options') as $opt) {
                $item->options()->create($opt);
            }
        }

        return response()->json($item->fresh('options'));
    }
    
    public function reorderItems(Request $request)
    {
        $items = $request->input('items'); // Array of {id, sort_order}
        
        DB::transaction(function () use ($items) {
            foreach ($items as $itemData) {
                SurveyItem::where('id', $itemData['id'])->update(['sort_order' => $itemData['sort_order']]);
            }
        });

        return response()->json(['status' => 'ok']);
    }
}
