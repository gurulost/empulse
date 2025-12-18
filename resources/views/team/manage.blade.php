@extends('layouts.app')

@section('title', 'Team Management')

@section('content')
<div id="team-management-app" data-user-role="{{ Auth::user()->role }}"></div>
@endsection
