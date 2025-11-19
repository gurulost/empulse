<template>
    <div class="impact-snapshot">
        <div class="row text-center">
            <div class="col-md-4 mb-3 mb-md-0">
                <p class="text-uppercase small text-muted mb-1">Current Impact</p>
                <p class="display-6 mb-0">{{ formatNumber(positive) }}</p>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <p class="text-uppercase small text-muted mb-1">Importance in Ideal Role</p>
                <p class="display-6 mb-0">{{ formatNumber(importance) }}</p>
            </div>
            <div class="col-md-4">
                <p class="text-uppercase small text-muted mb-1">Desire Gap</p>
                <p class="display-6 mb-0">{{ formatNumber(desire) }}</p>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ImpactSnapshot',
    props: {
        positive: { type: Number, default: null },
        importance: { type: Number, default: null },
        desire: { type: Number, default: null },
    },
    methods: {
        formatNumber(value) {
            if (value === null || value === undefined) return '—';
            return Number(value).toFixed(1);
        }
    }
};
</script>
