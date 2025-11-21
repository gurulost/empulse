<template>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden fade-in">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <div class="input-group" style="max-width: 300px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" class="form-control bg-light border-start-0 ps-0" placeholder="Search users..." :value="searchQuery" @input="$emit('update:searchQuery', $event.target.value)" @keyup.enter="$emit('search')">
            </div>
            <div v-if="companyFilter" class="badge bg-info-subtle text-info border border-info-subtle d-flex align-items-center gap-2 px-3 py-2 rounded-pill">
                Company ID: {{ companyFilter }}
                <button type="button" class="btn-close btn-close-dark" style="font-size: 0.5em;" aria-label="Close" @click="$emit('clear-filter')"></button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                <tr>
                    <th class="ps-4 py-3 text-secondary text-uppercase small fw-bold">User</th>
                    <th class="py-3 text-secondary text-uppercase small fw-bold">Role</th>
                    <th class="py-3 text-secondary text-uppercase small fw-bold">Company ID</th>
                    <th class="pe-4 py-3 text-secondary text-uppercase small fw-bold text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="user in users.data" :key="user.id">
                    <td class="ps-4 py-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-sm bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px;">
                                {{ user.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <div class="fw-bold text-dark">{{ user.name }}</div>
                                <div class="small text-muted">{{ user.email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="py-3">
                        <span class="badge rounded-pill px-3 py-2" :class="getRoleBadgeClass(user.role)">
                            {{ getRoleLabel(user.role) }}
                        </span>
                    </td>
                    <td class="py-3 text-muted">{{ user.company_id }}</td>
                    <td class="pe-4 py-3 text-end">
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary rounded-start-pill" @click="$emit('impersonate', user.id)" title="Login as this user">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </button>
                            <button class="btn btn-sm btn-outline-danger rounded-end-pill" @click="$emit('delete', user.id)" title="Delete User">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr v-if="users.data.length === 0">
                    <td colspan="4" class="text-center text-muted py-5">
                        <i class="bi bi-person-x fs-1 d-block mb-2"></i>
                        No users found.
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center py-3 px-4" v-if="users.last_page > 1">
            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" :disabled="users.current_page === 1" @click="$emit('page-change', users.current_page - 1)">Previous</button>
            <span class="small text-muted">Page {{ users.current_page }} of {{ users.last_page }}</span>
            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" :disabled="users.current_page === users.last_page" @click="$emit('page-change', users.current_page + 1)">Next</button>
        </div>
    </div>
</template>

<script setup>
defineProps({
    users: {
        type: Object,
        required: true
    },
    searchQuery: String,
    companyFilter: [String, Number]
});

defineEmits(['update:searchQuery', 'search', 'clear-filter', 'page-change', 'impersonate', 'delete']);

const getRoleLabel = (role) => {
    const roles = { 0: 'Super Admin', 1: 'Manager', 3: 'Team Lead', 4: 'Employee' };
    return roles[role] ?? 'Unknown';
};

const getRoleBadgeClass = (role) => {
    const classes = {
        0: 'bg-danger-subtle text-danger', // Super Admin
        1: 'bg-primary-subtle text-primary', // Manager
        3: 'bg-info-subtle text-info', // Team Lead
        4: 'bg-secondary-subtle text-secondary' // Employee
    };
    return classes[role] ?? 'bg-light text-dark';
};
</script>

<style scoped>
.fade-in {
    animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
