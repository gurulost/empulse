<table class="table table-striped">
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Department</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($users as $index => $user)
        <tr>
            <td class="number_in_table"><p>{{ $users->firstItem() + $index }}</p></td>
            <td class="p-name">{{ $user->name }}</td>
            <td class="p-email">{{ $user->email }}</td>
            <td>
                @switch($user->role)
                    @case(1) Manager @break
                    @case(2) Chief @break
                    @case(3) Team Lead @break
                    @case(4) Employee @break
                    @default Unknown
                @endswitch
            </td>
            <td>{{ $user->department ?? '-' }}</td>
            <td>
                <button class="btn btn-sm btn-danger confirmDeleteWorker" item_value="{{ $user->email }}">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" class="text-center">No members found</td>
        </tr>
        @endforelse
    </tbody>
</table>

@if($users->hasPages())
<nav>
    {{ $users->links() }}
</nav>
@endif
