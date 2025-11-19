<template>
    <div class="team-culture-pulse">
        <div class="row">
            <div class="col-md-4 mb-3 mb-md-0">
                <h6 class="text-muted text-uppercase small">Positive Signals</h6>
                <p class="display-6 mb-1">{{ formatNumber(positive) }}</p>
                <small class="text-muted">Average of trust, clarity, respect statements.</small>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <h6 class="text-muted text-uppercase small">Areas of Friction</h6>
                <p class="display-6 mb-1">{{ formatNumber(negative) }}</p>
                <small class="text-muted">Average of conflict, bureaucracy, pressure statements.</small>
            </div>
            <div class="col-md-4">
                <h6 class="text-muted text-uppercase small">Net Culture Score</h6>
                <div class="progress" style="height: 8px;">
                    <div
                        class="progress-bar"
                        :class="score >= 0 ? 'bg-success' : 'bg-danger'"
                        role="progressbar"
                        :style="{ width: scorePercent + '%' }"
                        :aria-valuenow="score"
                        aria-valuemin="-9"
                        aria-valuemax="9"
                    ></div>
                </div>
                <small class="text-muted">
                    {{ scoreText }}
                </small>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted text-uppercase small mb-2">Top Tension Drivers</h6>
                <ul class="list-unstyled mb-0">
                    <li v-for="item in negativeItems" :key="item.qid" class="d-flex justify-content-between">
                        <span>{{ item.qid }}</span>
                        <span class="badge bg-danger">{{ formatNumber(item.value) }}</span>
                    </li>
                    <li v-if="!negativeItems.length" class="text-muted">No responses yet.</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted text-uppercase small mb-2">Positive Reinforcers</h6>
                <ul class="list-unstyled mb-0">
                    <li v-for="item in positiveItems" :key="item.qid" class="d-flex justify-content-between">
                        <span>{{ item.qid }}</span>
                        <span class="badge bg-success">{{ formatNumber(item.value) }}</span>
                    </li>
                    <li v-if="!positiveItems.length" class="text-muted">No responses yet.</li>
                </ul>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'TeamCulturePulse',
    props: {
        score: { type: Number, default: null },
        positive: { type: Number, default: null },
        negative: { type: Number, default: null },
        positiveItems: { type: Array, default: () => [] },
        negativeItems: { type: Array, default: () => [] },
    },
    computed: {
        scorePercent() {
            if (this.score === null) return 0;
            // Map -9 to 9 range to 0-100%
            return Math.max(0, Math.min(((this.score + 9) / 18) * 100, 100));
        },
        scoreText() {
            if (this.score === null) return 'No data yet';
            return this.score >= 0 ? 'Above waterline' : 'Needs attention';
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
