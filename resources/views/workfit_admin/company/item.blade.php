@extends('layouts.workfit_admin')
@section('page_title')
    List of Company Workers
@endsection
@section('content')
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($list as $item)
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->email }}</td>
                            <td>{{ $item->role == 1 ? 'Manager' : ($item->role == 2 ? 'Chief' : ($item->role == 3 ? 'Teamlead' : 'Employee')) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                {{ $list->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection
