<template>
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Team Members</h5>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-success" @click="showImportModal = true">
                    <i class="bi bi-upload me-1"></i>Import
                </button>
                <button class="btn btn-sm btn-primary" @click="showAddModal = true">
                    <i class="bi bi-person-plus me-1"></i>Add Member
                </button>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Filters -->
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <input 
                        v-model="filters.search" 
                        type="text" 
                        class="form-control" 
                        placeholder="Search by name or email..."
                        @input="debouncedSearch"
                    >
                </div>
                <div class="col-md-3" v-if="canFilterRole">
                    <select v-model="filters.role" class="form-select" @change="loadMembers">
                        <option value="">All Roles</option>
                        <option v-for="role in availableRoles" :key="role.value" :value="role.value">
                            {{ role.label }}
                        </option>
                    </select>
                </div>
                <div class="col-md-3" v-if="userRole === 1">
                    <select v-model="filters.department" class="form-select" @change="loadMembers">
                        <option value="">All Departments</option>
                        <option v-for="dept in departments" :key="dept.title" :value="dept.title">
                            {{ dept.title }}
                        </option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100" @click="clearFilters">
                        <i class="bi bi-x-circle me-1"></i>Clear
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="text-center py-5">
                <SkeletonLoader height="3rem" class="mb-2" />
                <SkeletonLoader height="3rem" class="mb-2" />
                <SkeletonLoader height="3rem" class="mb-2" />
            </div>

            <!-- Table -->
            <div v-else-if="members.length > 0" class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th @click="sortBy('name')" class="cursor-pointer">
                                Name <i :class="getSortIcon('name')"></i>
                            </th>
                            <th @click="sortBy('email')" class="cursor-pointer">
                                Email <i :class="getSortIcon('email')"></i>
                            </th>
                            <th>Role</th>
                            <th v-if="userRole === 1">Department</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="member in members" :key="member.email">
                            <td>
                                <span v-html="highlightSearch(member.name)"></span>
                            </td>
                            <td>
                                <span v-html="highlightSearch(member.email)"></span>
                            </td>
                            <td>
                                <span class="badge" :class="getRoleBadgeClass(member.role)">
                                    {{ getRoleLabel(member.role) }}
                                </span>
                            </td>
                            <td v-if="userRole === 1">{{ member.department || '-' }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" @click="editMember(member)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" @click="confirmDelete(member)" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <nav v-if="pagination.last_page > 1" class="mt-3">
                    <ul class="pagination justify-content-center">
                        <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                            <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page - 1)">Previous</a>
                        </li>
                        <li 
                            v-for="page in visiblePages" 
                            :key="page" 
                            class="page-item" 
                            :class="{ active: page === pagination.current_page }"
                        >
                            <a class="page-link" href="#" @click.prevent="changePage(page)">{{ page }}</a>
                        </li>
                        <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                            <a class="page-link" href="#" @click.prevent="changePage(pagination.current_page + 1)">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-5 text-muted">
                <i class="bi bi-people display-4 mb-3 d-block"></i>
                <p>No team members found</p>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <AddMemberModal
            v-if="showAddModal || editingMember"
            :member="editingMember"
            :user-role="userRole"
            :departments="departments"
            @close="closeModal"
            @saved="handleSaved"
        />

        <!-- Import Modal -->
        <Teleport to="body">
            <div v-if="showImportModal" class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Import Team Members</h5>
                            <button type="button" class="btn-close" @click="showImportModal = false"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted mb-3">
                                <a :href="`/users/export/${userRole}`" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-download me-1"></i>Download Template
                                </a>
                            </p>
                            <input type="file" class="form-control" @change="handleFileUpload" accept=".xlsx,.xls">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showImportModal = false">Cancel</button>
                            <button type="button" class="btn btn-primary" @click="importFile" :disabled="!selectedFile">Import</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useTeamApi } from '../../composables/useTeamApi';
import { useToast } from '../../composables/useToast';
import SkeletonLoader from '../common/SkeletonLoader.vue';
import AddMemberModal from './AddMemberModal.vue';

const props = defineProps({
    userRole: { type: Number, required: true },
    departments: { type: Array, default: () => [] },
    canManageDepartments: { type: Boolean, default: false }
});

const emit = defineEmits(['refresh-departments']);

const api = useTeamApi();
const toast =useToast();

const loading = ref(false);
const members = ref([]);
const pagination = ref({ current_page: 1, last_page: 1 });
const filters = ref({ search: '', role: '', department: '' });
const sortKey = ref('name');
const sortDir = ref('asc');
const showAddModal = ref(false);
const showImportModal = ref(false);
const editingMember = ref(null);
const selectedFile = ref(null);

let searchTimeout = null;

const canFilterRole = computed(() => props.userRole === 1 || props.userRole === 2);

const availableRoles = computed(() => {
    if (props.userRole === 1) {
        return [
            { value: 1, label: 'Manager' },
            { value: 2, label: 'Chief' },
            { value: 3, label: 'Teamlead' },
            { value: 4, label: 'Employee' },
        ];
    } else if (props.userRole === 2) {
        return [
            { value: 3, label: 'Teamlead' },
            { value: 4, label: 'Employee' }
        ];
    }
    return [];
});

const visiblePages = computed(() => {
    const pages = [];
    const current = pagination.value.current_page;
    const last = pagination.value.last_page;
    
    for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
        pages.push(i);
    }
    return pages;
});

const loadMembers = async (page = 1) => {
    loading.value = true;
    try {
        const params = {
            page,
            q: filters.value.search,
            role: filters.value.role,
            department: filters.value.department,
            sort: sortKey.value,
            dir: sortDir.value
        };
        
        const response = await api.getTeamMembers(params);
        members.value = response.data;
        pagination.value = {
            current_page: response.current_page,
            last_page: response.last_page
        };
    } catch (error) {
        console.error('Failed to load members:', error);
        toast.error('Failed to load team members');
    } finally {
        loading.value = false;
    }
};

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        pagination.value.current_page = 1;
        loadMembers(1);
    }, 300);
};

const clearFilters = () => {
    filters.value = { search: '', role: '', department: '' };
    loadMembers(1);
};

const sortBy = (key) => {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = 'asc';
    }
    loadMembers(pagination.value.current_page);
};

const getSortIcon = (key) => {
    if (sortKey.value !== key) return 'bi bi-chevron-expand text-muted';
    return sortDir.value === 'asc' ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
};

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        pagination.value.current_page = page;
        loadMembers(page);
    }
};

const highlightSearch = (text) => {
    if (!text) return '';
    // Escape HTML to prevent XSS
    const escapedText = text.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");

    if (!filters.value.search) return escapedText;
    
    // Escape special regex characters in search term
    const search = filters.value.search.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp(`(${search})`, 'gi');
    return escapedText.replace(regex, '<mark>$1</mark>');
};

const getRoleLabel = (role) => {
    const labels = { 1: 'Manager', 2: 'Chief', 3: 'Teamlead', 4: 'Employee' };
    return labels[role] || 'Unknown';
};

const getRoleBadgeClass = (role) => {
    const classes = {
        1: 'bg-primary',
        2: 'bg-info',
        3: 'bg-success',
        4: 'bg-secondary'
    };
    return classes[role] || 'bg-secondary';
};

const editMember = (member) => {
    editingMember.value = { ...member };
};

const confirmDelete = async (member) => {
    if (confirm(`Are you sure you want to delete ${member.name}?`)) {
        try {
            await api.deleteTeamMember(member.email);
            toast.success('Team member deleted successfully');
            loadMembers(pagination.value.current_page);
        } catch (error) {
            toast.error('Failed to delete team member');
        }
    }
};

const closeModal = () => {
    showAddModal.value = false;
    editingMember.value = null;
};

const handleSaved = () => {
    closeModal();
    loadMembers(pagination.value.current_page);
    emit('refresh-departments');
};

const handleFileUpload = (event) => {
    selectedFile.value = event.target.files[0];
};

const importFile = async () => {
    if (!selectedFile.value) return;
    
    try {
        await api.importUsers(selectedFile.value);
        toast.success('Users imported successfully');
        showImportModal.value = false;
        selectedFile.value = null;
        loadMembers(1);
        emit('refresh-departments');
    } catch (error) {
        toast.error(error.response?.data?.message || 'Import failed');
    }
};

onMounted(() => {
    loadMembers(1);
});
</script>

<style scoped>
.cursor-pointer {
    cursor: pointer;
    user-select: none;
}

mark {
    background-color: #fef3c7;
    padding: 0 2px;
}
</style>
