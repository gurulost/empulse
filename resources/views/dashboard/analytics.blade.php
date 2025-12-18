@extends('layouts.app')

@section('title', 'Analytics Dashboard')

@section('content')
    @php
        $initialCompanyId = Auth::user()->company_id ?: (\App\Models\Companies::orderBy('id')->value('id') ?? 0);
    @endphp

    <analytics-dashboard
        :user="{{ Auth::user() }}"
        :initial-company-id="{{ (int) $initialCompanyId }}"
    ></analytics-dashboard>
@endsection
