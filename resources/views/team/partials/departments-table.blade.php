<table class="table table-striped">
    <thead>
        <tr>
            <th>#</th>
            <th>Department Name</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($departments as $index => $dept)
        <tr class="departmentTR" department_id="{{ $dept->id }}">
            <td>{{ $departments->firstItem() + $index }}</td>
            <td>
                <span class="title-{{ $dept->id }} text-depart-table">{{ $dept->title }}</span>
                <form class="title-{{ $dept->id }}-newTitle" style="display: none;" method="POST" action="/team/api/departments/{{ urlencode($dept->title) }}">
                    @csrf
                    @method('PUT')
                    <input type="text" name="newTitle" class="form-control newTitle" value="{{ $dept->title }}">
                    <button type="submit" class="btn btn-sm btn-success ok mt-1">Save</button>
                    <button type="button" class="btn btn-sm btn-secondary cancel-form-{{ $dept->id }} mt-1">Cancel</button>
                </form>
            </td>
            <td>
                <button class="btn btn-sm btn-primary editDepartment">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-danger confirmDeleteDepartment" item_value="{{ $dept->title }}">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="3" class="text-center">No departments found</td>
        </tr>
        @endforelse
    </tbody>
</table>

@if($departments->hasPages())
<nav>
    {{ $departments->links() }}
</nav>
@endif
