@extends('layouts.app')

@section('content')
    @php
        $user = Auth::user();
        $isWorkfitAdmin = (int) ($user->is_admin ?? 0) === 1 || (int) ($user->role ?? 0) === 0;
        $initialCompanyId = $user->company_id ? (int) $user->company_id : null;
        $companies = $isWorkfitAdmin
            ? \App\Models\Companies::query()
                ->select('id', 'title')
                ->orderBy('title')
                ->get()
            : collect();
        $jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
    @endphp

    <div class="container-fluid py-4">
        <div
            id="reports-dashboard-root"
            data-user='@json($user, $jsonFlags)'
            data-initial-company-id='@json($initialCompanyId ?? "", $jsonFlags)'
            data-companies='@json($companies, $jsonFlags)'
        ></div>
    </div>
@endsection
