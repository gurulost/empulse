@extends('layouts.app')

@section('title', 'Analytics Dashboard')

@section('content')
    <analytics-dashboard 
        :user="{{ Auth::user() }}"
        :initial-company-id="{{ Auth::user()->company_id }}"
    ></analytics-dashboard>
@endsection
