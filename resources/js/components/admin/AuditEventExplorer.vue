<template>
    <section aria-labelledby="audit-event-explorer-title">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h3 id="audit-event-explorer-title" class="h6 fw-bold">Audit event explorer</h3>
                <p class="small text-muted">
                    Investigate attributable privileged activity without exposing stored change payloads, tokens, or
                    respondent answers. Every successful view is recorded in the platform audit stream.
                </p>

                <form class="row g-2 align-items-end" @submit.prevent="$emit('search')">
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold" for="audit-company-id">Company ID</label>
                        <input
                            id="audit-company-id"
                            class="form-control"
                            inputmode="numeric"
                            min="1"
                            type="number"
                            :value="companyId"
                            @input="$emit('update:companyId', $event.target.value)"
                        >
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold" for="audit-action">Exact action</label>
                        <input
                            id="audit-action"
                            class="form-control"
                            maxlength="100"
                            placeholder="member.deactivated"
                            type="text"
                            :value="action"
                            @input="$emit('update:action', $event.target.value)"
                        >
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold" for="audit-subject-id">Subject ID</label>
                        <input
                            id="audit-subject-id"
                            class="form-control"
                            maxlength="255"
                            type="text"
                            :value="subjectId"
                            @input="$emit('update:subjectId', $event.target.value)"
                        >
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-primary" type="submit">Search</button>
                    </div>
                </form>
            </div>
        </div>

        <div
            class="alert"
            :class="report.integrity?.valid ? 'alert-success' : 'alert-danger'"
            role="status"
        >
            <strong>{{ report.integrity?.valid ? 'Integrity verified' : 'Integrity failure' }}</strong>
            for <code>{{ report.integrity?.stream || 'unknown stream' }}</code>
            <span v-if="report.integrity?.events !== null && report.integrity?.events !== undefined">
                ({{ report.integrity.events }} events)
            </span>
            <span v-if="report.integrity?.failed_event_id">
                — first failing event ID {{ report.integrity.failed_event_id }}
            </span>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <caption class="visually-hidden">Recent privileged audit events</caption>
                    <thead class="table-light">
                        <tr>
                            <th>Occurred</th>
                            <th>Company / stream</th>
                            <th>Action</th>
                            <th>Actor</th>
                            <th>Subject</th>
                            <th>Sequence</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="event in report.events?.data || []" :key="event.id">
                            <td class="small text-nowrap">{{ formatDate(event.occurred_at) }}</td>
                            <td>
                                <span v-if="event.company">{{ event.company.title }} (#{{ event.company.id }})</span>
                                <code v-else>{{ event.stream }}</code>
                            </td>
                            <td><code>{{ event.action }}</code></td>
                            <td>
                                <span v-if="event.actor">{{ event.actor.name }} (#{{ event.actor.id }})</span>
                                <span v-else class="text-muted">System</span>
                            </td>
                            <td>
                                <span v-if="event.subject">
                                    {{ event.subject.type }}<span v-if="event.subject.id"> #{{ event.subject.id }}</span>
                                </span>
                                <span v-else class="text-muted">—</span>
                            </td>
                            <td>{{ event.sequence }}</td>
                        </tr>
                        <tr v-if="!(report.events?.data || []).length">
                            <td colspan="6" class="text-center text-muted py-4">
                                No audit events match these exact filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="(report.events?.last_page || 1) > 1"
                class="card-footer bg-white d-flex justify-content-between align-items-center"
            >
                <span class="small text-muted">
                    Page {{ report.events.current_page }} of {{ report.events.last_page }}
                </span>
                <div class="btn-group btn-group-sm">
                    <button
                        class="btn btn-outline-secondary"
                        type="button"
                        :disabled="report.events.current_page <= 1"
                        @click="$emit('page-change', report.events.current_page - 1)"
                    >
                        Previous
                    </button>
                    <button
                        class="btn btn-outline-secondary"
                        type="button"
                        :disabled="report.events.current_page >= report.events.last_page"
                        @click="$emit('page-change', report.events.current_page + 1)"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
defineProps({
    report: {
        type: Object,
        required: true,
    },
    companyId: {
        type: String,
        default: '',
    },
    action: {
        type: String,
        default: '',
    },
    subjectId: {
        type: String,
        default: '',
    },
});

defineEmits([
    'search',
    'page-change',
    'update:companyId',
    'update:action',
    'update:subjectId',
]);

const formatDate = (value) => value
    ? new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value))
    : '—';
</script>
