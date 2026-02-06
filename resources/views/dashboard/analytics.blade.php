@extends('layouts.app')

@section('title', 'Analytics Dashboard')

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

    <analytics-dashboard
        :user='@json($user, $jsonFlags)'
        :initial-company-id='@json($initialCompanyId ?? "", $jsonFlags)'
        :companies='@json($companies, $jsonFlags)'
    ></analytics-dashboard>
@endsection
