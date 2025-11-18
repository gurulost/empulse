@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    @php $sampleAssignment = $assignments->first(); @endphp
                    <div>
                        <h5 class="mb-0">Internal Survey</h5>
                        <small class="text-muted">Preview of the placeholder assessment.</small>
                    </div>
                    <a href="{{ $sampleAssignment ? route('survey.take', $sampleAssignment->token) : '#' }}" class="btn btn-outline-primary btn-sm {{ $sampleAssignment ? '' : 'disabled' }}" target="_blank" rel="noreferrer">Open sample</a>
                </div>
                <div class="card-body">
                    @if($survey)
                        <ul class="list-group list-group-flush">
                            @foreach($survey->questions as $question)
                                <li class="list-group-item">
                                    <strong>{{ $question->title }}</strong>
                                    <p class="mb-0 text-muted">Type: {{ ucfirst($question->question_type) }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mb-0">No survey configured yet.</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Latest submissions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                            <tr>
                                <th>User</th>
                                <th>Status</th>
                                <th>Submitted at</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($assignments as $assignment)
                                <tr>
                                    <td>{{ optional($assignment->user)->email ?? 'Removed user' }}</td>
                                    <td>
                                        @if($assignment->status === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($assignment->response)->submitted_at?->format('M d, Y H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-3">No assignments found.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    {{ $assignments->links() }}
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Snapshot</h5>
                </div>
                <div class="card-body">
                    @if($analytics && count($analytics['data']) > 0)
                        <p class="text-muted">{{ count($analytics['data']) }} recent submissions processed.</p>
                        <p class="small text-muted">The dashboard now reads from the internal survey store.</p>
                    @else
                        <p class="text-muted">Responses will appear here once teammates start submitting the new survey.</p>
                    @endif
                    <a href="{{ route('home') }}" class="btn btn-outline-secondary btn-sm">View dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
