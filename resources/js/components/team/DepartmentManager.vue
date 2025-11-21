<template>
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Departments</h5>
            <button class="btn btn-sm btn-primary" @click="showAddModal = true">
                <i class="bi bi-plus-circle me-1"></i>Add Department
            </button>
        </div>
        
        <div class="card-body">
            <!-- Loading State -->
            <div v-if="loading" class="text-center py-5">
                <SkeletonLoader height="3rem" class="mb-2" />
                <SkeletonLoader height="3rem" class="mb-2" />
                <SkeletonLoader height="3rem" class="mb-2" />
            </div>

            <!-- Table -->
            <div v-else-if="departmentList.length > 0" class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Department Name</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(dept, index) in departmentList" :key="dept.title">
                            <td>
                                <span v-if="editingIndex !== index">{{ dept.title }}</span>
                                <input 
                                    v-else 
                                    v-model="editForm.title" 
                                    type="text" 
                                    class="form-control form-control-sm"
                                    @keyup.enter="saveEdit(dept.title)"
                                    @keyup.esc="cancelEdit"
                                >
                            </td>
                            <td class="text-end">
                                <div v-if="editingIndex !== index" class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" @click="startEdit(index, dept.title)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" @click="confirmDelete(dept.title)" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <div v-else class="btn-group btn-group-sm">
                                    <button class="btn btn-success" @click="saveEdit(dept.title)" title="Save">
                                        <i class="bi bi-check"></i>
                                    </button>
                                    <button class="btn btn-secondary" @click="cancelEdit" title="Cancel">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-5 text-muted">
                <i class="bi bi-building display-4 mb-3 d-block"></i>
                <p>No departments found</p>
            </div>
        </div>

        <!-- Add Department Modal -->
        <Teleport to="body">
            <div v-if="showAddModal" class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Department</h5>
                            <button type="button" class="btn-close" @click="closeAddModal"></button>
                        </div>
                        <form @submit.prevent="addDepartment">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Department Name <span class="text-danger">*</span></label>
                                    <input 
                                        v-model="newDepartment" 
                                        type="text" 
                                        class="form-control"
                                        placeholder="Max. 50 characters"
                                        maxlength="50"
                                        required
                                    >
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" @click="closeAddModal">Cancel</button>
                                <button type="submit" class="btn btn-primary" :disabled="!newDepartment.trim() || saving">
                                    <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                                    Add
                                </button>
                            </div>
                        </form>
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

const props = defineProps({
    departments: { type: Array, default: () => [] }
});

const emit = defineEmits(['refresh']);

const api = useTeamApi();
const toast = useToast();

const loading = ref(false);
const departmentList = ref([]);
const showAddModal = ref(false);
const newDepartment = ref('');
const editingIndex = ref(null);
const editForm = ref({ title: '' });
const saving = ref(false);

const loadDepartments = async () => {
    loading.value = true;
    try {
        const response = await api.getDepartments();
        // Handle both paginated response and simple array if API changes
        departmentList.value = response.data || response;
    } catch (error) {
        console.error('Failed to load departments:', error);
        toast.error('Failed to load departments');
    } finally {
        loading.value = false;
    }
};

const addDepartment = async () => {
    if (!newDepartment.value.trim()) return;
    
    saving.value = true;
    try {
        await api.addDepartment(newDepartment.value.trim());
        toast.success('Department added successfully');
        closeAddModal();
        loadDepartments();
        emit('refresh');
    } catch (error) {
        const message = error.response?.data?.message || 'Failed to add department';
        toast.error(message);
    } finally {
        saving.value = false;
    }
};

const startEdit = (index, title) => {
    editingIndex.value = index;
    editForm.value.title = title;
};

const cancelEdit = () => {
    editingIndex.value = null;
    editForm.value.title = '';
};

const saveEdit = async (oldTitle) => {
    if (!editForm.value.title.trim() || editForm.value.title === oldTitle) {
        cancelEdit();
        return;
    }
    
    try {
        await api.updateDepartment(oldTitle, editForm.value.title.trim());
        toast.success('Department updated successfully');
        cancelEdit();
        loadDepartments();
        emit('refresh');
    } catch (error) {
        const message = error.response?.data?.message || 'Failed to update department';
        toast.error(message);
    }
};

const confirmDelete = async (title) => {
    if (confirm(`Are you sure you want to delete the "${title}" department?`)) {
        try {
            await api.deleteDepartment(title);
            toast.success('Department deleted successfully');
            loadDepartments();
            emit('refresh');
        } catch (error) {
            const message = error.response?.data?.message || 'Failed to delete department. It may have users assigned to it.';
            toast.error(message);
        }
    }
};

const closeAddModal = () => {
    showAddModal.value = false;
    newDepartment.value = '';
};

onMounted(() => {
    // Use prop data if available, otherwise load
    if (props.departments.length > 0) {
        departmentList.value = [...props.departments];
    } else {
        loadDepartments();
    }
});
</script>
