Vue.component('feeder-graph', {
    props: ['feeder', 'type'],
    data() {
        return {
            chart: null
        };
    },
    computed: {
        mainHist() {
            return this.type?.[this.feeder] || [];
        }
    },
    mounted() {
        if (this.mainHist.length) {
            this.renderChart();
        }
    },
    watch: {
        mainHist(newVal) {
            if (this.chart) this.chart.destroy();
            if (newVal.length) {
                this.renderChart();
            }
        }
    },
    methods: {
        renderChart() {
            const ctx = this.$refs.canvas.getContext('2d');

            const labels = this.mainHist.map(item => item.x);
            const pValues = this.mainHist.map(item => item.p ?? null);
            const qValues = this.mainHist.map(item => item.q ?? null);
            const vaValues = this.mainHist.map(item => item.va ?? null);

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'P',
                            borderColor: 'red',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            data: pValues
                        },
                        {
                            label: 'Q',
                            borderColor: 'blue',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            data: qValues
                        },
                        {
                            label: 'VA',
                            borderColor: 'green',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            data: vaValues
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    scales: {
                        x: {
                            display: false
                        },
                        y: {
                            ticks: {
                                beginAtZero: true
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                fontSize: 12
                            }
                        }
                    },
                    elements: {
                        point: {
                            radius: 2
                        }
                    }
                }
            });
        }
    },
    template: `
    <div class="w3-card-4 w3-border w3-padding w3-margin-bottom">
        <div class="w3-row">
            <div class="w3-col l12 w3-center">
                <strong>24-Hour Historical Chart</strong>
            </div>
        </div>
        <hr>
        <div class="w3-row" style="height: 300px;">
            <canvas ref="canvas"></canvas>
        </div>
        <hr>
    </div>
    `
});
