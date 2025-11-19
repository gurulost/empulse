<template>
    <Teleport to="body">
        <div class="modal d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ isEditing ? 'Edit' : 'Add' }} Team Member</h5>
                        <button type="button" class="btn-close" @click="$emit('close')"></button>
                    </div>
                    <form @submit.prevent="handleSubmit">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input 
                                    v-model="form.name" 
                                    type="text" 
                                    class="form-control" 
                                    :class="{ 'is-invalid': errors.name }"
                                    placeholder="Min. 5 characters"
                                >
                                <div class="invalid-feedback">{{ errors.name }}</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email <span class="text-danger">*</span></label>
                                <input 
                                    v-model="form.email" 
                                    type="email" 
                                    class="form-control"
                                    :class="{ 'is-invalid': errors.email }"
                                    :disabled="isEditing"
                                    placeholder="user@example.com"
                                >
                                <div class="invalid-feedback">{{ errors.email }}</div>
                            </div>

                            <!-- Role Selection for Manager -->
                            <div v-if="userRole === 1" class="mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" v-model="form.role" :value="1" id="roleManager">
                                    <label class="form-check-label" for="roleManager">Manager</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" v-model="form.role" :value="2" id="roleChief">
                                    <label class="form-check-label" for="roleChief">Chief</label>
                                </div>
                            </div>

                            <!-- Role Selection for Chief -->
                            <div v-if="userRole === 2" class="mb-3">
                                <label class="form-label">Role <span class="text-danger">*</span></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" v-model="form.role" :value="3" id="roleTeamlead">
                                    <label class="form-check-label" for="roleTeamlead">Teamlead</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" v-model="form.role" :value="4" id="roleEmployee" checked>
                                    <label class="form-check-label" for="roleEmployee">Employee</label>
                                </div>
                            </div>

                            <!-- Department Selection (for Chief role or Manager) -->
                            <div v-if="showDepartmentField" class="mb-3">
                                <label class="form-label">Department</label>
                                <select v-model="form.department" class="form-select">
                                    <option value="">None</option>
                                    <option v-for="dept in departments" :key="dept.title" :value="dept.title">
                                        {{ dept.title }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="$emit('close')">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="!isValid || saving">
                                <span v-if="saving" class="spinner-border spinner-border-sm me-2"></span>
                                {{ isEditing ? 'Update' : 'Add' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useTeamApi } from '../../composables/useTeamApi';
import { useToast } from '../../composables/useToast';

const props = defineProps({
    member: { type: Object, default: null },
    userRole: { type: Number, required: true },
    departments: { type: Array, default: () => [] }
});

const emit = defineEmits(['close', 'saved']);

const api = useTeamApi();
const toast = useToast();

const isEditing = computed(() => !!props.member);

const form = ref({
    name: props.member?.name || '',
    email: props.member?.email || '',
    role: props.member?.role || (props.userRole === 1 ? 1 : 4),
    department: props.member?.department || ''
});

const errors = ref({});
const saving = ref(false);

const showDepartmentField = computed(() => {
    return props.userRole === 1 || (props.userRole === 2 && form.value.role);
});

const isValid = computed(() => {
    return form.value.name.length >= 5 && 
           /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email) &&
           form.value.role;
});

watch(() => form.value.name, (value) => {
    if (value.length > 0 && value.length < 5) {
        errors.value.name = 'Name must be at least 5 characters';
    } else {
        delete errors.value.name;
    }
});

watch(() => form.value.email, (value) => {
    if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
        errors.value.email = 'Please enter a valid email address';
    } else {
        delete errors.value.email;
    }
});

const handleSubmit = async () => {
    if (!isValid.value) return;
    
    saving.value = true;
    try {
        const data = {
            name: form.value.name,
            email: form.value.email,
            role: form.value.role,
            department: form.value.department || null
        };

        if (isEditing.value) {
            await api.updateTeamMember(props.member.email, {
                new_name: data.name,
                new_email: data.email,
                new_role: data.role,
                new_department: data.department
            });
            toast.success('Team member updated successfully');
        } else {
            await api.addTeamMember(data);
            toast.success('Team member added successfully');
        }
        
        emit('saved');
    } catch (error) {
        const message = error.response?.data?.message || 'Operation failed';
        toast.error(message);
    } finally {
        saving.value = false;
    }
};
</script>
