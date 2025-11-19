<template>
    <div class="temperature-gauge text-center">
        <small class="text-muted d-block mb-1">Weighted Indicator</small>
        <div class="position-relative d-inline-block" style="width: 100%; max-width: 200px;">
            <!-- Simple semi-circle gauge using CSS/SVG could go here, for now using a styled progress bar with a 'gauge' look -->
            <div class="progress" style="height: 12px; border-radius: 6px;">
                <div
                    class="progress-bar bg-gradient-success"
                    role="progressbar"
                    :style="{ width: percent + '%' }"
                    :aria-valuenow="score"
                    aria-valuemin="0"
                    aria-valuemax="10"
                ></div>
            </div>
        </div>
        <div class="fw-bold mt-1 fs-5">{{ formatNumber(score) }}/10</div>
    </div>
</template>

<script>
export default {
    name: 'TemperatureGauge',
    props: {
        score: { type: Number, default: 0 },
    },
    computed: {
        percent() {
            return Math.max(0, Math.min((this.score / 10) * 100, 100));
        }
    },
    methods: {
        formatNumber(value) {
            if (value === null || value === undefined) return '—';
            return Number(value).toFixed(1);
        }
    }
};
</script>

<style scoped>
.bg-gradient-success {
    background: linear-gradient(90deg, #dc3545 0%, #ffc107 50%, #198754 100%);
}
</style>
