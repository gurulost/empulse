<?php

namespace App\Http\Controllers;

use App\Models\DataSubjectRequest;
use App\Models\User;
use App\Services\PrivacyGovernanceService;
use Illuminate\Http\Request;

class PrivacyRequestController extends Controller
{
    public function __construct(protected PrivacyGovernanceService $privacy) {}

    public function index(Request $request)
    {
        $query = DataSubjectRequest::with('subject:id,name,email,company_id,status')
            ->orderByDesc('id');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->integer('company_id'));
        }

        return response()->json($query->paginate(50));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject_user_id' => 'required|integer|exists:users,id',
            'type' => 'required|in:access,correction,erasure',
            'reason' => 'nullable|string|max:2000',
            'requested_changes' => 'nullable|array',
            'requested_changes.name' => 'sometimes|string|max:255',
            'requested_changes.email' => 'sometimes|email:rfc|max:125',
        ]);
        $subject = User::findOrFail($data['subject_user_id']);
        $case = $this->privacy->createRequest(
            $subject,
            $data['type'],
            $request->user(),
            $data['reason'] ?? null,
            $data['requested_changes'] ?? []
        );

        return response()->json($case, 201);
    }

    public function verifyIdentity(Request $request, DataSubjectRequest $privacyRequest)
    {
        return response()->json(
            $this->privacy->verifyIdentity($privacyRequest, $request->user())
        );
    }

    public function approve(Request $request, DataSubjectRequest $privacyRequest)
    {
        return response()->json(
            $this->privacy->approve($privacyRequest, $request->user())
        );
    }

    public function execute(Request $request, DataSubjectRequest $privacyRequest)
    {
        $result = $this->privacy->execute($privacyRequest, $request->user());

        if ($privacyRequest->type === 'access') {
            return response()->json($result)
                ->header('Cache-Control', 'no-store, private')
                ->header('Content-Disposition', "attachment; filename=empulse-data-{$privacyRequest->public_id}.json");
        }

        return response()->json($result);
    }
}
