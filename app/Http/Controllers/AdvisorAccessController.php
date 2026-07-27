<?php

namespace App\Http\Controllers;

use App\Models\AdvisorCompanyGrant;
use App\Models\User;
use App\Services\AdvisorAccessService;
use Illuminate\Http\Request;

class AdvisorAccessController extends Controller
{
    public function store(Request $request, AdvisorAccessService $access)
    {
        $data = $request->validate([
            'advisor_user_id' => 'required|integer|exists:users,id',
            'purpose' => 'required|string|min:10|max:2000',
            'valid_until' => 'nullable|date|after:today',
        ]);
        $companyId = (int) $request->user()->company_id;
        abort_unless($companyId > 0, 422, 'Company context is required.');

        $access->grant(
            $companyId,
            User::findOrFail($data['advisor_user_id']),
            $request->user(),
            $data['purpose'],
            isset($data['valid_until']) ? new \DateTimeImmutable($data['valid_until']) : null
        );

        return redirect()->route('actions.index')->with('status', 'Advisor access granted.');
    }

    public function destroy(Request $request, AdvisorCompanyGrant $advisorGrant, AdvisorAccessService $access)
    {
        $access->revoke($advisorGrant, $request->user());

        return redirect()->route('actions.index')->with('status', 'Advisor access revoked.');
    }
}
