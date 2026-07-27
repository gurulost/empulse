<template>
    <section>
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-8">
                        <label for="value-company-id" class="form-label">Organization ID</label>
                        <input
                            id="value-company-id"
                            type="number"
                            min="1"
                            class="form-control"
                            :value="companyId"
                            placeholder="Leave blank for the platform view"
                            @input="$emit('update:companyId', $event.target.value)"
                        >
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-primary w-100" @click="$emit('search')">Apply scope</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-info small">
            This stable, versioned report uses organization workflow records only. It contains no survey answers or employee identities, and “movement observed” is not a causal claim.
        </div>

        <div class="row g-3 mb-4">
            <div v-for="metric in headlineMetrics" :key="metric.label" class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="small text-uppercase text-muted fw-semibold">{{ metric.label }}</div>
                        <div class="display-6 fw-bold mt-2">{{ formatRate(metric.value) }}</div>
                        <div class="small text-muted">{{ metric.detail }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white"><h3 class="h6 mb-0">Action-loop funnel</h3></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Stage</th><th class="text-end">Count</th></tr></thead>
                    <tbody>
                        <tr v-for="row in funnelRows" :key="row.key">
                            <td>{{ row.label }}</td>
                            <td class="text-end fw-semibold">{{ report.counts[row.key] || 0 }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="report.organizations?.length" class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h3 class="h6 mb-0">Organization conversion</h3></div>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Organization</th>
                            <th>Reliable findings</th>
                            <th>With action</th>
                            <th>With measured outcome</th>
                            <th>Finding → action</th>
                            <th>Finding → measurement</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="organization in report.organizations" :key="organization.company_id">
                            <td>{{ organization.title }}</td>
                            <td>{{ organization.reliable_findings }}</td>
                            <td>{{ organization.findings_with_action }}</td>
                            <td>{{ organization.findings_with_measured_outcome }}</td>
                            <td>{{ formatRate(organization.finding_to_action_pct) }}</td>
                            <td>{{ formatRate(organization.finding_to_measured_outcome_pct) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    report: { type: Object, required: true },
    companyId: { type: [String, Number], default: '' },
});

defineEmits(['update:companyId', 'search']);

const headlineMetrics = computed(() => [
    {
        label: 'Reliable findings → action',
        value: props.report.rates?.finding_to_action_pct,
        detail: `${props.report.counts?.findings_with_action || 0} of ${props.report.counts?.reliable_findings || 0}`,
    },
    {
        label: 'Reliable findings → measurement plan',
        value: props.report.rates?.finding_to_measurement_plan_pct,
        detail: `${props.report.counts?.findings_with_measurement_plan || 0} of ${props.report.counts?.reliable_findings || 0}`,
    },
    {
        label: 'Reliable findings → measured outcome',
        value: props.report.rates?.finding_to_measured_outcome_pct,
        detail: `${props.report.counts?.findings_with_measured_outcome || 0} of ${props.report.counts?.reliable_findings || 0}`,
    },
]);

const funnelRows = [
    { key: 'reliable_findings', label: 'Reliable findings captured' },
    { key: 'findings_with_action', label: 'Findings with an owned action' },
    { key: 'findings_with_measurement_plan', label: 'Findings with a predeclared measurement plan' },
    { key: 'findings_with_measured_outcome', label: 'Findings with an immutable follow-up outcome' },
];

const formatRate = (value) => value === null || value === undefined ? 'Not yet measurable' : `${value}%`;
</script>
