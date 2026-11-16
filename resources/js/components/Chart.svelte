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

    // Selaras dengan token desain: tinta, cabai, daun, kunyit, lalu nada tanah
    const PALETTE = ['#1b1813', '#b93a1a', '#4d7a46', '#c98a1b', '#8a6f5a', '#5f7f8c', '#a69d90', '#d6cdbd'];
    const INK = '#1b1813';
    const MUTED = '#776e62';
    const GRID = '#ece6da';

    Chart.defaults.font.family = "'Plus Jakarta Sans Variable', system-ui, sans-serif";
    Chart.defaults.font.size = 12;
    Chart.defaults.color = MUTED;

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
                    borderWidth: isDoughnut ? 2 : type === 'line' ? 2 : 0,
                    borderRadius: type === 'bar' ? 8 : 0,
                    borderSkipped: false,
                    maxBarThickness: 36,
                    tension: 0.35,
                    fill: type === 'line',
                    backgroundColor: isDoughnut ? PALETTE : type === 'line' ? 'rgba(185,58,26,0.07)' : PALETTE[index % PALETTE.length],
                    borderColor: isDoughnut ? '#fffdf9' : type === 'line' ? '#b93a1a' : PALETTE[index % PALETTE.length],
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    pointBackgroundColor: '#b93a1a',
                    ...dataset,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: horizontal ? 'y' : 'x',
                plugins: {
                    legend: { display: isDoughnut || datasets.length > 1, position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', boxWidth: 6, boxHeight: 6, padding: 16 } },
                    tooltip: {
                        backgroundColor: INK,
                        titleColor: '#f6f2ea',
                        bodyColor: '#f6f2ea',
                        padding: 10,
                        cornerRadius: 10,
                        displayColors: false,
                        callbacks: { label: (ctx) => `${ctx.dataset.label ?? ctx.label}: ${formatValue(ctx.raw)}` },
                    },
                },
                cutout: isDoughnut ? '68%' : undefined,
                interaction: isDoughnut ? undefined : { mode: 'index', intersect: false },
                scales: isDoughnut ? {} : {
                    [horizontal ? 'x' : 'y']: {
                        beginAtZero: true,
                        border: { display: false },
                        grid: { color: GRID },
                        ticks: { maxTicksLimit: 5, callback: (v) => (money ? rupiahShort(v) : v) },
                    },
                    [horizontal ? 'y' : 'x']: { border: { display: false }, grid: { display: false }, ticks: { color: horizontal ? INK : MUTED } },
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
