@extends('layouts.workfit_admin')
@section('page_title')
    List of Subscriptions
@endsection
@section('content')
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        <th>Plan</th>
                        <th>Price</th>
                        <th>Count Companies</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($list as $item)
                        <tr>
                            <td>{{ $item->tariff ? $item->tariff : 'Without Subscription' }}</td>
                            <td>{{ $item->tariff ? '199$' : 'None' }}</td>
                            <td>{{ $item->count_companies }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                {{ $list->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
@endsection