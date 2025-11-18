<template>
    <div>
        <div
            v-for="indicator in indicators"
            :key="indicator.key ?? indicator.label"
            class="mb-3"
        >
            <div class="d-flex justify-content-between align-items-center mb-1">
                <strong class="me-2">{{ indicator.label }}</strong>
                <span class="badge" :class="gapBadge(indicator.gap)">
                    {{ formatNumber(indicator.gap) }}
                </span>
            </div>
            <div class="progress" style="height: 8px;">
                <div
                    class="progress-bar bg-primary"
                    role="progressbar"
                    :style="{ width: currentWidth(indicator) }"
                ></div>
            </div>
            <small class="text-muted">
                Current {{ formatNumber(indicator.current) }} / Ideal {{ formatNumber(indicator.ideal) }}
            </small>
        </div>
        <p v-if="!indicators.length" class="text-muted mb-0">No indicator data yet.</p>
    </div>
</template>

<script>
export default {
    name: 'IndicatorList',
    props: {
        indicators: {
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
        currentWidth(indicator) {
            if (indicator.current === null || indicator.current === undefined) {
                return '0%';
            }

            const pct = Math.max(0, Math.min((indicator.current / 10) * 100, 100));
            return `${pct}%`;
        },
        gapBadge(gap) {
            if (gap === null || gap === undefined) {
                return 'bg-secondary';
            }
            return gap >= 0 ? 'bg-danger' : 'bg-success';
        },
    },
};
</script>
