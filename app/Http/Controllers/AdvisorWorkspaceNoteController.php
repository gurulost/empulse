<?php

namespace App\Http\Controllers;

use App\Services\AdvisorAccessService;
use App\Services\AdvisorWorkspaceNoteService;
use Illuminate\Http\Request;

class AdvisorWorkspaceNoteController extends Controller
{
    public function store(
        Request $request,
        AdvisorAccessService $access,
        AdvisorWorkspaceNoteService $notes
    ) {
        $data = $request->validate([
            'company_id' => 'nullable|integer|exists:companies,id',
            'visibility' => 'required|in:customer_shared,workfit_internal',
            'body' => 'required|string|min:2|max:10000',
        ]);

        try {
            $companyId = $access->companyIdForActor(
                $request->user(),
                isset($data['company_id']) ? (int) $data['company_id'] : null
            );
            $notes->create(
                $request->user(),
                $companyId,
                $data['visibility'],
                $data['body']
            );
        } catch (\DomainException $exception) {
            abort(403, $exception->getMessage());
        }

        return redirect()
            ->route('actions.index', $request->user()->hasCapability('actions.advisor')
                ? ['company_id' => $companyId]
                : [])
            ->with('status', 'Workspace note recorded.');
    }
}
