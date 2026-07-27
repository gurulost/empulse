<template>
    <section class="border rounded-3 p-3 mb-3 bg-light" aria-labelledby="roster-import-title">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <h6 id="roster-import-title" class="fw-bold mb-1">Governed roster import</h6>
                <p class="small text-muted mb-0">
                    Upload CSV, review every proposed change, then confirm once. No roster data changes during preview.
                </p>
            </div>
            <button type="button" class="btn-close" aria-label="Close roster import" @click="emit('close')"></button>
        </div>

        <div class="alert alert-secondary small mt-3 mb-3">
            Required headers: <code>external_id,name,email,role</code>. Optional:
            <code>department,supervisor_external_id,status</code>. Departments must already exist. Status may be
            <code>active</code> or <code>inactive</code>.
        </div>

        <form class="row g-2 align-items-end" @submit.prevent="upload">
            <div class="col-md">
                <label class="form-label small fw-semibold" for="roster-import-file">Roster CSV</label>
                <input
                    id="roster-import-file"
                    ref="fileInput"
                    class="form-control"
                    type="file"
                    accept=".csv,text/csv"
                    :disabled="busy"
                    required
                    @change="selectFile"
                >
            </div>
            <div class="col-md-auto">
                <button class="btn btn-primary" type="submit" :disabled="busy || !file">
                    <span v-if="busy" class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>
                    {{ busy ? 'Preparing preview…' : 'Preview Import' }}
                </button>
            </div>
        </form>

        <div class="visually-hidden" role="status" aria-live="polite">{{ statusMessage }}</div>
        <div v-if="errorMessage" class="alert alert-danger mt-3 mb-0" role="alert">{{ errorMessage }}</div>

        <div v-if="preview" class="mt-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <div class="fw-semibold">{{ preview.filename }}</div>
                    <div class="small text-muted">Status: {{ statusLabel }}</div>
                </div>
                <a
                    class="btn btn-sm btn-outline-secondary"
                    :href="`/team/api/roster-imports/${preview.id}/result.csv`"
                >
                    Download row results
                </a>
            </div>

            <div class="row g-2 mb-3">
                <div v-for="metric in countMetrics" :key="metric.label" class="col-6 col-md">
                    <div class="bg-white border rounded p-2 h-100">
                        <div class="small text-muted">{{ metric.label }}</div>
                        <div class="fw-bold">{{ metric.value }}</div>
                    </div>
                </div>
            </div>

            <div v-if="preview.status === 'parsing'" class="alert alert-info mb-0">
                This larger file is being parsed by the queue. The preview will refresh automatically.
            </div>

            <div v-else-if="preview.failure_summary" class="alert alert-warning">
                {{ preview.failure_summary }}
            </div>

            <div v-if="preview.rows?.length" class="table-responsive roster-preview-table">
                <table class="table table-sm table-hover align-middle mb-0">
                    <caption class="visually-hidden">Roster import preview rows and validation outcomes</caption>
                    <thead>
                        <tr>
                            <th>Row</th>
                            <th>External ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Action</th>
                            <th>Errors</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in preview.rows" :key="row.row_number">
                            <td>{{ row.row_number }}</td>
                            <td>{{ row.external_id || '—' }}</td>
                            <td>{{ row.name || '—' }}</td>
                            <td>{{ row.email || '—' }}</td>
                            <td>{{ roleLabel(row.role) }}</td>
                            <td>{{ row.department || '—' }}</td>
                            <td><span class="badge" :class="actionBadge(row.action)">{{ row.action }}</span></td>
                            <td>
                                <ul v-if="row.errors?.length" class="small text-danger ps-3 mb-0">
                                    <li v-for="message in row.errors" :key="message">{{ message }}</li>
                                </ul>
                                <span v-else class="text-muted">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="preview.status === 'preview_ready'" class="border-top mt-3 pt-3">
                <div class="form-check">
                    <input id="confirm-roster-preview" v-model="reviewed" class="form-check-input" type="checkbox">
                    <label class="form-check-label" for="confirm-roster-preview">
                        I reviewed the create, update, reactivation, deactivation, and unchanged counts and every row.
                    </label>
                </div>
                <button
                    type="button"
                    class="btn btn-success mt-3"
                    :disabled="busy || !reviewed"
                    @click="commit"
                >
                    {{ busy ? 'Committing…' : 'Commit Reviewed Changes' }}
                </button>
            </div>

            <div v-if="preview.status === 'committed'" class="alert alert-success mt-3 mb-0">
                The roster changes were committed atomically. New and reactivated people have account invitations queued.
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { useTeamApi } from '../../composables/useTeamApi';

const emit = defineEmits(['close', 'committed']);
const api = useTeamApi();
const file = ref(null);
const fileInput = ref(null);
const preview = ref(null);
const confirmationToken = ref('');
const busy = ref(false);
const reviewed = ref(false);
const errorMessage = ref('');
const statusMessage = ref('');
let pollTimer = null;

const countMetrics = computed(() => {
    const counts = preview.value?.counts ?? {};
    return [
        { label: 'Create', value: counts.create ?? 0 },
        { label: 'Update', value: counts.update ?? 0 },
        { label: 'Reactivate', value: counts.reactivate ?? 0 },
        { label: 'Deactivate', value: counts.deactivate ?? 0 },
        { label: 'Unchanged', value: counts.unchanged ?? 0 },
        { label: 'Errors', value: counts.errors ?? 0 },
    ];
});

const statusLabel = computed(() => ({
    parsing: 'Parsing securely',
    invalid: 'Needs correction',
    preview_ready: 'Ready for review',
    committed: 'Committed',
    failed: 'Processing failed',
}[preview.value?.status] ?? preview.value?.status ?? 'Unknown'));

const selectFile = (event) => {
    file.value = event.target.files?.[0] ?? null;
    preview.value = null;
    confirmationToken.value = '';
    reviewed.value = false;
    errorMessage.value = '';
    stopPolling();
};

const upload = async () => {
    if (!file.value) return;
    busy.value = true;
    errorMessage.value = '';
    statusMessage.value = 'Uploading roster and preparing a preview.';
    try {
        const response = await api.stageRosterImport(file.value);
        preview.value = response.data;
        confirmationToken.value = response.confirmation_token ?? '';
        statusMessage.value = response.queued
            ? 'Roster accepted and queued for parsing.'
            : 'Roster preview is ready.';
        if (response.queued) startPolling();
    } catch (error) {
        errorMessage.value = apiError(error, 'The roster preview could not be prepared.');
    } finally {
        busy.value = false;
    }
};

const refresh = async () => {
    if (!preview.value?.id) return;
    try {
        const response = await api.getRosterImport(preview.value.id);
        preview.value = response.data;
        if (preview.value.status !== 'parsing') {
            stopPolling();
            if (preview.value.status === 'preview_ready') {
                const confirmation = await api.issueRosterImportConfirmation(preview.value.id);
                confirmationToken.value = confirmation.confirmation_token;
                statusMessage.value = 'Roster preview is ready for review.';
            }
        }
    } catch (error) {
        stopPolling();
        errorMessage.value = apiError(error, 'The roster preview status could not be refreshed.');
    }
};

const commit = async () => {
    if (!reviewed.value || !preview.value?.id) return;
    busy.value = true;
    errorMessage.value = '';
    statusMessage.value = 'Committing the reviewed roster changes.';
    try {
        if (!confirmationToken.value) {
            const confirmation = await api.issueRosterImportConfirmation(preview.value.id);
            confirmationToken.value = confirmation.confirmation_token;
        }
        const response = await api.commitRosterImport(preview.value.id, confirmationToken.value);
        preview.value = response.data;
        confirmationToken.value = '';
        statusMessage.value = 'Roster changes committed successfully.';
        emit('committed');
    } catch (error) {
        reviewed.value = false;
        errorMessage.value = apiError(error, 'The roster import was not committed. Refresh the preview and try again.');
    } finally {
        busy.value = false;
    }
};

const startPolling = () => {
    stopPolling();
    pollTimer = window.setInterval(refresh, 2000);
};

const stopPolling = () => {
    if (pollTimer) {
        window.clearInterval(pollTimer);
        pollTimer = null;
    }
};

const apiError = (error, fallback) => {
    const errors = error?.response?.data?.errors;
    if (errors) return Object.values(errors).flat().join(' ');
    return error?.response?.data?.message || fallback;
};

const roleLabel = (role) => ({
    1: 'Manager',
    2: 'Chief',
    3: 'Teamlead',
    4: 'Employee',
}[Number(role)] ?? '—');

const actionBadge = (action) => ({
    create: 'text-bg-success',
    update: 'text-bg-primary',
    reactivate: 'text-bg-info',
    deactivate: 'text-bg-warning',
    unchanged: 'text-bg-secondary',
    invalid: 'text-bg-danger',
}[action] ?? 'text-bg-secondary');

onBeforeUnmount(stopPolling);
</script>

<style scoped>
.roster-preview-table {
    max-height: 26rem;
}

.roster-preview-table thead {
    position: sticky;
    top: 0;
    z-index: 1;
}
</style>
