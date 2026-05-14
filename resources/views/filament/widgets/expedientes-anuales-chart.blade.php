<x-filament-widgets::widget>
    <x-filament::section heading="Flujo Anual — Expedientes, Ingresos y Egresos ({{ $year }})">
        <div wire:ignore style="position:relative; height:380px">
            <canvas id="flujoAnualChart"></canvas>
        </div>
    </x-filament::section>

    @once
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    @endonce

    <script>
    (function () {
        var labels   = @json($labels);
        var datasets = @json($datasets);

        function boot() {
            var el = document.getElementById('flujoAnualChart');
            if (!el) return;
            if (el._chartInstance) { el._chartInstance.destroy(); }
            el._chartInstance = new Chart(el, {
                type: 'line',
                data: { labels: labels, datasets: datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'bottom', labels: { boxWidth: 12 } }
                    },
                    scales: {
                        y: {
                            type: 'linear',
                            position: 'left',
                            title: { display: true, text: 'Expedientes', color: '#9ca3af' },
                            ticks: { stepSize: 1, color: '#9ca3af' },
                            grid: { color: 'rgba(156,163,175,0.1)' }
                        },
                        y1: {
                            type: 'linear',
                            position: 'right',
                            title: { display: true, text: 'Miles MXN ($)', color: '#9ca3af' },
                            ticks: { color: '#9ca3af' },
                            grid: { drawOnChartArea: false }
                        },
                        x: {
                            ticks: { color: '#9ca3af' },
                            grid: { color: 'rgba(156,163,175,0.1)' }
                        }
                    }
                }
            });
        }

        // Esperar a que Chart.js del CDN esté cargado
        function waitAndBoot() {
            if (typeof Chart !== 'undefined') { boot(); }
            else { setTimeout(waitAndBoot, 50); }
        }
        waitAndBoot();
    })();
    </script>
</x-filament-widgets::widget>
