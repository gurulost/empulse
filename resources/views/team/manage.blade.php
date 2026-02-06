@extends('layouts.app')

@section('title', 'Team Management')

@section('content')
    @if(!$hasCompanyContext)
        <div class="container py-4">
            <div class="alert alert-warning mb-0">
                No company context found for your account. Team Management is available after assigning a company.
            </div>
        </div>
    @else
        <div id="team-management-app" data-user-role="{{ Auth::user()->role }}"></div>
    @endif
@endsection
