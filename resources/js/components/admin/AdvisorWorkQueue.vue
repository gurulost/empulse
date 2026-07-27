<template>
    <section>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="advisor-queue-status" class="form-label">Status</label>
                        <select
                            id="advisor-queue-status"
                            class="form-select"
                            :value="status"
                            @change="$emit('update:status', $event.target.value)"
                        >
                            <option value="">All statuses</option>
                            <option value="open">Open</option>
                            <option value="claimed">Claimed</option>
                            <option value="completed">Completed</option>
                            <option value="dismissed">Dismissed</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label for="advisor-queue-kind" class="form-label">Work type</label>
                        <select
                            id="advisor-queue-kind"
                            class="form-select"
                            :value="kind"
                            @change="$emit('update:kind', $event.target.value)"
                        >
                            <option value="">All work types</option>
                            <option value="activation_risk">Activation risk</option>
                            <option value="finding_review">Finding review</option>
                            <option value="action_plan_assistance">Action-plan assistance</option>
                            <option value="overdue_followup">Overdue follow-up</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" @click="$emit('search')">Apply filters</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-info small">
            Only work for organizations that currently grant you advisor access is shown. Queue records contain workflow metadata, not survey answers.
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Priority</th>
                            <th>Organization</th>
                            <th>Work</th>
                            <th>Evidence context</th>
                            <th>Status</th>
                            <th>Due</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in report.items.data" :key="item.id">
                            <td>
                                <span class="badge" :class="priorityClass(item.priority)">{{ item.priority }}</span>
                            </td>
                            <td>{{ item.company.title }}</td>
                            <td>{{ label(item.kind) }}</td>
                            <td class="small">
                                <span v-if="item.finding">{{ item.finding.metric_id }}</span>
                                <span v-else-if="item.action">{{ item.action.title }}</span>
                                <span v-else class="text-muted">Organization activation</span>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ item.status }}</span>
                                <div v-if="item.assignee" class="small text-muted mt-1">{{ item.assignee.name }}</div>
                            </td>
                            <td class="small">{{ item.due_at ? formatDate(item.due_at) : '—' }}</td>
                            <td class="text-end">
                                <div class="d-flex flex-wrap justify-content-end gap-1">
                                    <a
                                        :href="`/actions?company_id=${item.company.id}`"
                                        class="btn btn-sm btn-outline-primary"
                                    >Open workspace</a>
                                    <button
                                        v-if="item.status === 'open'"
                                        class="btn btn-sm btn-primary"
                                        @click="$emit('transition', item, 'claimed')"
                                    >Claim</button>
                                    <button
                                        v-if="item.status === 'claimed'"
                                        class="btn btn-sm btn-success"
                                        @click="$emit('transition', item, 'completed')"
                                    >Complete</button>
                                    <button
                                        v-if="['open', 'claimed'].includes(item.status)"
                                        class="btn btn-sm btn-outline-secondary"
                                        @click="$emit('transition', item, 'dismissed')"
                                    >Dismiss</button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!report.items.data.length">
                            <td colspan="7" class="text-center py-5 text-muted">
                                No authorized advisor work matches these filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-if="report.items.last_page > 1" class="card-footer bg-white d-flex justify-content-between align-items-center">
                <button
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="report.items.current_page <= 1"
                    @click="$emit('page-change', report.items.current_page - 1)"
                >Previous</button>
                <span class="small text-muted">Page {{ report.items.current_page }} of {{ report.items.last_page }}</span>
                <button
                    class="btn btn-sm btn-outline-secondary"
                    :disabled="report.items.current_page >= report.items.last_page"
                    @click="$emit('page-change', report.items.current_page + 1)"
                >Next</button>
            </div>
        </div>
    </section>
</template>

<script setup>
defineProps({
    report: { type: Object, required: true },
    status: { type: String, default: '' },
    kind: { type: String, default: '' },
});

defineEmits([
    'update:status',
    'update:kind',
    'search',
    'page-change',
    'transition',
]);

const labels = {
    activation_risk: 'Activation risk',
    finding_review: 'Finding review',
    action_plan_assistance: 'Action-plan assistance',
    overdue_followup: 'Overdue follow-up',
};

const label = (kind) => labels[kind] || kind;
const priorityClass = (priority) => ({
    urgent: 'bg-danger',
    high: 'bg-warning text-dark',
    normal: 'bg-info text-dark',
}[priority] || 'bg-secondary');
const formatDate = (value) => new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
}).format(new Date(value));
</script>
