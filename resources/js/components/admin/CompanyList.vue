<template>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden fade-in">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                <tr>
                    <th class="ps-4 py-3 text-secondary text-uppercase small fw-bold">Company</th>
                    <th class="py-3 text-secondary text-uppercase small fw-bold">Manager</th>
                    <th class="py-3 text-secondary text-uppercase small fw-bold">Plan</th>
                    <th class="pe-4 py-3 text-secondary text-uppercase small fw-bold text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="company in companies.data" :key="company.id">
                    <td class="ps-4 py-3">
                        <div class="fw-bold text-dark">{{ company.title }}</div>
                        <div class="small text-muted">ID: {{ company.id }}</div>
                    </td>
                    <td class="py-3">
                        <div class="text-dark">{{ company.manager }}</div>
                        <div class="small text-muted">{{ company.manager_email }}</div>
                    </td>
                    <td class="py-3">
                        <span class="badge rounded-pill px-3 py-2" 
                              :class="company.tariff === 1 ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'">
                            {{ company.tariff === 1 ? 'Premium' : 'Free' }}
                        </span>
                    </td>
                    <td class="pe-4 py-3 text-end">
                        <button class="btn btn-sm btn-light text-primary fw-medium" @click="$emit('view-company', company)">
                            View Users <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </td>
                </tr>
                <tr v-if="companies.data.length === 0">
                    <td colspan="4" class="text-center text-muted py-5">
                        <i class="bi bi-building-slash fs-1 d-block mb-2"></i>
                        No companies found.
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center py-3 px-4" v-if="companies.last_page > 1">
            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" :disabled="companies.current_page === 1" @click="$emit('page-change', companies.current_page - 1)">Previous</button>
            <span class="small text-muted">Page {{ companies.current_page }} of {{ companies.last_page }}</span>
            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" :disabled="companies.current_page === companies.last_page" @click="$emit('page-change', companies.current_page + 1)">Next</button>
        </div>
    </div>
</template>

<script setup>
defineProps({
    companies: {
        type: Object,
        required: true
    }
});

defineEmits(['view-company', 'page-change']);
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
