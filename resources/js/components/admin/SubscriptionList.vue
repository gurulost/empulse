<template>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden fade-in">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                <tr>
                    <th class="ps-4 py-3 text-secondary text-uppercase small fw-bold">ID</th>
                    <th class="py-3 text-secondary text-uppercase small fw-bold">User</th>
                    <th class="py-3 text-secondary text-uppercase small fw-bold">Plan</th>
                    <th class="py-3 text-secondary text-uppercase small fw-bold">Status</th>
                    <th class="pe-4 py-3 text-secondary text-uppercase small fw-bold text-end">Ends At</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="sub in subscriptions.data" :key="sub.id">
                    <td class="ps-4 py-3 text-muted">#{{ sub.id }}</td>
                    <td class="py-3">
                        <div class="fw-bold text-dark">{{ sub.user_name }}</div>
                        <div class="small text-muted">{{ sub.user_email }}</div>
                    </td>
                    <td class="py-3">
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3">{{ sub.name }}</span>
                    </td>
                    <td class="py-3">
                        <span class="badge rounded-pill px-3" 
                              :class="isActive(sub) ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'">
                            {{ isActive(sub) ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="pe-4 py-3 text-end text-muted">
                        {{ formatDate(sub.ends_at) }}
                    </td>
                </tr>
                <tr v-if="subscriptions.data.length === 0">
                    <td colspan="5" class="text-center text-muted py-5">
                        <i class="bi bi-credit-card fs-1 d-block mb-2"></i>
                        No subscriptions found.
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white border-top d-flex justify-content-between align-items-center py-3 px-4" v-if="subscriptions.last_page > 1">
            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" :disabled="subscriptions.current_page === 1" @click="$emit('page-change', subscriptions.current_page - 1)">Previous</button>
            <span class="small text-muted">Page {{ subscriptions.current_page }} of {{ subscriptions.last_page }}</span>
            <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" :disabled="subscriptions.current_page === subscriptions.last_page" @click="$emit('page-change', subscriptions.current_page + 1)">Next</button>
        </div>
    </div>
</template>

<script setup>
defineProps({
    subscriptions: {
        type: Object,
        required: true
    }
});

defineEmits(['page-change']);

const isActive = (sub) => {
    return sub.stripe_status === 'active' || sub.stripe_status === 'trialing';
};

const formatDate = (date) => {
    if (!date) return 'Never';
    return new Date(date).toLocaleDateString();
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
