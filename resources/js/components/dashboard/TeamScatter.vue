<template>
    <div class="team-scatter">
        <p v-if="!points.length" class="text-muted mb-0">No team data yet.</p>
        <div v-else>
            <canvas ref="canvas" class="w-100 mb-3" height="320"></canvas>
            <table class="table table-sm align-middle">
                <thead class="table-light">
                <tr>
                    <th>Group</th>
                    <th>Level</th>
                    <th class="text-center">People</th>
                    <th class="text-center">Indicator (X)</th>
                    <th class="text-center">Culture (Y)</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="point in points" :key="point.label">
                    <td>{{ point.label }}</td>
                    <td>{{ capitalize(point.level) }}</td>
                    <td class="text-center">{{ point.count }}</td>
                    <td class="text-center">{{ formatNumber(point.indicator) }}</td>
                    <td class="text-center">{{ formatNumber(point.culture) }}</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
export default {
    name: 'TeamScatter',
    props: {
        points: {
            type: Array,
            default: () => [],
        },
    },
    mounted() {
        this.draw();
    },
    watch: {
        points: {
            handler() {
                this.draw();
            },
            deep: true,
        },
    },
    methods: {
        capitalize(value) {
            if (!value) {
                return '';
            }
            return value.charAt(0).toUpperCase() + value.slice(1);
        },
        formatNumber(value) {
            if (value === null || value === undefined) {
                return '—';
            }
            return Number(value).toFixed(2);
        },
        draw() {
            const canvas = this.$refs.canvas;
            if (!canvas || !this.points.length) {
                return;
            }

            const ctx = canvas.getContext('2d');
            const width = canvas.width;
            const height = canvas.height;
            ctx.clearRect(0, 0, width, height);

            const padding = 40;
            const plotWidth = width - padding * 2;
            const plotHeight = height - padding * 2;

            // Axis ranges
            const minX = 1;
            const maxX = 10;
            const minY = -9;
            const maxY = 9;

            // Draw axes
            ctx.strokeStyle = '#d1d5db';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(padding, padding);
            ctx.lineTo(padding, height - padding);
            ctx.lineTo(width - padding, height - padding);
            ctx.stroke();

            // Axis labels
            ctx.fillStyle = '#6b7280';
            ctx.font = '12px sans-serif';
            ctx.fillText('Team Culture Evaluation', padding - 35, padding - 10);
            ctx.fillText('Weighted Indicator Satisfaction', width / 2 - 60, height - 10);

            this.points.forEach((point) => {
                const xValue = Number(point.indicator ?? minX);
                const yValue = Number(point.culture ?? minY);

                const xPct = (xValue - minX) / (maxX - minX);
                const yPct = (yValue - minY) / (maxY - minY);

                const x = padding + xPct * plotWidth;
                const y = height - padding - yPct * plotHeight;

                const radius = 8;
                ctx.beginPath();
                ctx.fillStyle = '#2563eb';
                ctx.globalAlpha = 0.7;
                ctx.arc(x, y, radius, 0, Math.PI * 2);
                ctx.fill();

                ctx.globalAlpha = 1;
                ctx.fillStyle = '#111827';
                ctx.font = '11px sans-serif';
                ctx.fillText(point.label ?? 'n/a', x + 10, y - 5);
            });
        },
    },
};
</script>
