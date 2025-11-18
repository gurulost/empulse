<template>
    <div class="gap-chart">
        <div
            v-for="item in items"
            :key="item.key ?? item.label"
            class="mb-3"
        >
            <div class="d-flex justify-content-between align-items-center mb-1">
                <strong class="me-2 flex-grow-1">{{ item.label }}</strong>
                <span class="badge" :class="gapBadgeClass(item.gap)">
                    {{ formatNumber(item.gap) }}
                </span>
            </div>
            <div class="progress" style="height: 8px;">
                <div
                    class="progress-bar bg-primary"
                    role="progressbar"
                    :style="{ width: barWidth(item) }"
                ></div>
            </div>
            <small class="text-muted">
                Current {{ formatNumber(item.current) }} / Ideal {{ formatNumber(item.ideal) }}
            </small>
        </div>
        <p v-if="!items.length" class="text-muted mb-0">No gap data available yet.</p>
    </div>
</template>

<script>
export default {
    name: 'GapChart',
    props: {
        items: {
            type: Array,
            default: () => [],
        },
    },
    methods: {
        formatNumber(value) {
            if (value === null || value === undefined) {
                return '—';
            }
            return Number(value).toFixed(1);
        },
        gapBadgeClass(gap) {
            if (gap === null || gap === undefined) {
                return 'bg-secondary';
            }
            return gap >= 0 ? 'bg-danger' : 'bg-success';
        },
        barWidth(item) {
            if (!item || item.current === undefined || item.current === null) {
                return '0%';
            }

            const pct = Math.max(0, Math.min((item.current / 10) * 100, 100));
            return `${pct}%`;
        },
    },
};
</script>
