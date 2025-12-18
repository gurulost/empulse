@extends('layouts.app')

@section('title', 'Super Admin Dashboard')

@section('content')
    <admin-dashboard :user="{{ Auth::user() }}"></admin-dashboard>
@endsection
