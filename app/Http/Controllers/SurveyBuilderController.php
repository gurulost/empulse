<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Models\SurveyItem;
use App\Models\SurveyPage;
use App\Models\SurveySection;
use App\Models\SurveyVersion;
use App\Services\SurveyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SurveyBuilderController extends Controller
{
    protected SurveyService $surveyService;

    public function __construct(SurveyService $surveyService)
    {
        $this->middleware(['auth', 'capability:survey-builder.manage']);
        $this->surveyService = $surveyService;
    }

    public function index()
    {
        $version = SurveyVersion::orderByDesc('is_active')->orderByDesc('id')->first();
        $survey = Survey::orderByDesc('is_default')->orderBy('id')->first();

        return view('surveys.builder', [
            'versionId' => $version?->id,
            'surveyId' => $survey?->id,
        ]);
    }

    public function getStructure($versionId)
    {
        $version = SurveyVersion::with([
            'pages' => function ($q) {
                $q->orderBy('sort_order');
            },
            'pages.sections' => function ($q) {
                $q->orderBy('sort_order');
            },
            'pages.items' => function ($q) {
                $q->orderBy('sort_order');
            },
            'pages.sections.items' => function ($q) {
                $q->orderBy('sort_order');
            },
            'pages.items.options',
            'pages.items.optionSource',
            'pages.sections.items.options',
            'pages.sections.items.optionSource',
        ])->findOrFail($versionId);

        return response()->json($version);
    }

    public function createDraft(Request $request, $surveyId)
    {
        $survey = Survey::findOrFail($surveyId);

        $latestVersion = SurveyVersion::where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if (! $latestVersion) {
            $latestVersion = SurveyVersion::orderByDesc('id')->first();
        }

        if (! $latestVersion) {
            return response()->json(['message' => 'No base version found'], 404);
        }

        $draft = $this->surveyService->cloneVersion($latestVersion);

        return response()->json(['draft_id' => $draft->id]);
    }

    public function submitForReview(Request $request, $versionId)
    {
        $version = SurveyVersion::findOrFail($versionId);
        $data = $request->validate([
            'change_summary' => 'required|string|min:10|max:4000',
        ]);

        try {
            $review = $this->surveyService->submitVersionForReview(
                $version,
                $request->user(),
                $data['change_summary']
            );
        } catch (\DomainException $exception) {
            return response()->json([
                'message' => 'Survey version failed review checks.',
                'errors' => [$exception->getMessage()],
            ], 422);
        }

        return response()->json(['status' => 'in_review', 'data' => $review]);
    }

    public function approveVersion(Request $request, $versionId)
    {
        $version = SurveyVersion::findOrFail($versionId);

        try {
            $approved = $this->surveyService->approveVersion($version, $request->user());
        } catch (\DomainException $exception) {
            return response()->json([
                'message' => 'Survey version failed approval checks.',
                'errors' => [$exception->getMessage()],
            ], 422);
        }

        return response()->json(['status' => 'approved', 'data' => $approved]);
    }

    public function publishVersion(Request $request, $versionId)
    {
        $version = SurveyVersion::findOrFail($versionId);

        if ($version->is_active) {
            return response()->json(['message' => 'Already active'], 422);
        }

        try {
            $this->surveyService->publishVersion($version, $request->user());
        } catch (\DomainException $exception) {
            return response()->json([
                'message' => 'Survey version failed publication checks.',
                'errors' => [$exception->getMessage()],
            ], 422);
        }

        return response()->json(['status' => 'published']);
    }

    public function updateItem(Request $request, $itemId)
    {
        $item = SurveyItem::findOrFail($itemId);
        $optionTypes = ['dropdown', 'single_select', 'single_select_text', 'multi_select'];

        if ($item->version->is_active || $item->version->publication_status !== 'draft') {
            return response()->json(['message' => 'Only a draft version can be edited'], 403);
        }

        $validated = $request->validate([
            'question' => 'required|string',
            'type' => 'required|string|in:slider,text,text_short,text_long,number_integer,dropdown,single_select,single_select_text,multi_select',
            'scale_config' => 'nullable|array',
            'scale_config.min' => 'nullable|numeric',
            'scale_config.max' => 'nullable|numeric|gte:scale_config.min',
            'scale_config.step' => 'nullable|numeric|gt:0',
            'scale_config.left_label' => 'nullable|string|max:255',
            'scale_config.right_label' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
            'display_logic' => 'nullable|array',
            'options' => 'nullable|array',
            'options.*.value' => 'required',
            'options.*.label' => 'required|string|max:255',
            'options.*.exclusive' => 'nullable|boolean',
            'options.*.meta' => 'nullable|array',
        ]);

        $item->update([
            'question' => $validated['question'],
            'type' => $validated['type'],
            'scale_config' => $validated['type'] === 'slider' ? ($validated['scale_config'] ?? null) : null,
            'metadata' => $validated['metadata'] ?? null,
            'display_logic' => $validated['display_logic'] ?? null,
        ]);

        if (! in_array($validated['type'], $optionTypes, true)) {
            $item->options()->delete();
            $item->optionSource()->delete();
        } elseif (array_key_exists('options', $validated)) {
            $item->options()->delete();
            foreach ($validated['options'] as $index => $opt) {
                $item->options()->create([
                    'value' => $opt['value'],
                    'label' => $opt['label'],
                    'exclusive' => (bool) ($opt['exclusive'] ?? false),
                    'meta' => $opt['meta'] ?? null,
                    'sort_order' => $index,
                ]);
            }
        }

        return response()->json($item->fresh(['options', 'optionSource']));
    }

    public function updatePage(Request $request, $pageId)
    {
        $page = SurveyPage::with('version')->findOrFail($pageId);

        if ($page->version?->is_active || $page->version?->publication_status !== 'draft') {
            return response()->json(['message' => 'Only a draft version can be edited'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $page->update(['title' => $validated['title']]);

        return response()->json($page->fresh());
    }

    public function updateSection(Request $request, $sectionId)
    {
        $section = SurveySection::with('page.version')->findOrFail($sectionId);

        if ($section->page?->version?->is_active || $section->page?->version?->publication_status !== 'draft') {
            return response()->json(['message' => 'Only a draft version can be edited'], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $section->update(['title' => $validated['title']]);

        return response()->json($section->fresh());
    }

    public function reorderItems(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer|exists:survey_items,id',
            'items.*.sort_order' => 'required|integer|min:0',
        ]);

        $items = collect($validated['items']);
        $versions = SurveyItem::query()
            ->with('version')
            ->whereIn('id', $items->pluck('id'))
            ->get();
        if ($versions->count() !== $items->count()
            || $versions->contains(fn (SurveyItem $item) => $item->version->is_active
                || $item->version->publication_status !== 'draft')) {
            return response()->json(['message' => 'Only items in one editable draft can be reordered'], 403);
        }
        if ($versions->pluck('survey_version_id')->unique()->count() !== 1) {
            return response()->json(['message' => 'Items from different versions cannot be reordered together'], 422);
        }

        DB::transaction(function () use ($items) {
            foreach ($items as $itemData) {
                $item = SurveyItem::findOrFail($itemData['id']);
                $item->update(['sort_order' => $itemData['sort_order']]);
            }
        });

        return response()->json(['status' => 'ok']);
    }
}
