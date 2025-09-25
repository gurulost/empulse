@extends('layouts.workfit_admin')
@section('page_title')
    List of Companies
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
                        <th>Manager</th>
                        <th>Manager Email</th>
                        <th>Plan</th>
                        <th>Price</th>
                        <th>Subscription Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($list as $item)
                        <tr>
                            <td>{{ $item->id }}</td>
                            <td>
                                <a href="{{ route('admin.company.item', ['id' => $item->id]) }}">{{ $item->title }}</a>
                            </td>
                            <td>{{ $item->manager }}</td>
                            <td>{{ $item->manager_email }}</td>
                            <td>{{ $item->tariff ? $item->tariff : 'None' }}</td>
                            <td>{{ $item->tariff ? '199$' : 'None' }}</td>
                            <td class="font-weight-bold text-{{ $item->tariff ? 'success' : 'danger' }} text-uppercase">{{ $item->tariff ? 'Active' : 'Not active' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                {{ $list->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection