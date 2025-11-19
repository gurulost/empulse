@extends('layouts.app')

@section('title', 'Team Management')

@section('content')
<div id="team-management-app"></div>
@endsection

@section('script')
<script>
    window.addEventListener('DOMContentLoaded', () => {
        const userRole = {{ Auth::user()->role }};
        const app = document.getElementById('team-management-app');
        if (app) {
            app.setAttribute('data-user-role', userRole);
        }
    });
</script>
@endsection
