<script>
    import { onDestroy } from 'svelte';
    import {
        Chart, BarController, LineController, DoughnutController, BarElement, LineElement, PointElement, ArcElement,
        CategoryScale, LinearScale, Tooltip, Legend, Filler,
    } from 'chart.js';
    import { rupiah } from '../lib/format.js';

    Chart.register(BarController, LineController, DoughnutController, BarElement, LineElement, PointElement, ArcElement, CategoryScale, LinearScale, Tooltip, Legend, Filler);

    /** Pembungkus Chart.js. money=true memformat nilai sebagai Rupiah. */
    let { type = 'bar', labels = [], datasets = [], money = true, height = 260, horizontal = false } = $props();

    let canvas = $state();
    let chart;

    const PALETTE = ['#dc2626', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#14b8a6', '#78716c'];

    function formatValue(value) {
        return money ? rupiah(value) : new Intl.NumberFormat('id-ID').format(value);
    }

    function buildConfig() {
        const isDoughnut = type === 'doughnut';
        return {
            type,
            data: {
                labels,
                datasets: datasets.map((dataset, index) => ({
                    borderWidth: isDoughnut ? 0 : 2,
                    borderRadius: type === 'bar' ? 6 : 0,
                    tension: 0.3,
                    fill: type === 'line',
                    backgroundColor: isDoughnut ? PALETTE : type === 'line' ? 'rgba(220,38,38,0.08)' : PALETTE[index % PALETTE.length],
                    borderColor: isDoughnut ? undefined : PALETTE[index % PALETTE.length],
                    pointRadius: 2,
                    ...dataset,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: horizontal ? 'y' : 'x',
                plugins: {
                    legend: { display: isDoughnut || datasets.length > 1, position: 'bottom' },
                    tooltip: { callbacks: { label: (ctx) => `${ctx.dataset.label ?? ctx.label}: ${formatValue(ctx.raw)}` } },
                },
                scales: isDoughnut ? {} : {
                    [horizontal ? 'x' : 'y']: { beginAtZero: true, ticks: { callback: (v) => (money ? rupiahShort(v) : v) } },
                    [horizontal ? 'y' : 'x']: { grid: { display: false } },
                },
            },
        };
    }

    function rupiahShort(value) {
        if (value >= 1_000_000) return `${(value / 1_000_000).toLocaleString('id-ID')} jt`;
        if (value >= 1_000) return `${(value / 1_000).toLocaleString('id-ID')} rb`;
        return value;
    }

    $effect(() => {
        const config = buildConfig();
        if (!canvas) return;
        chart?.destroy();
        chart = new Chart(canvas, config);
    });

    onDestroy(() => chart?.destroy());
</script>

<div style="height: {height}px" class="relative">
    <canvas bind:this={canvas}></canvas>
</div>
