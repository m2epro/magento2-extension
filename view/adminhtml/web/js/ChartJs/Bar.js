define([
    'jquery',
    'M2ePro/External/ChartJs/Chart.min',
    'M2ePro/External/ChartJs/es6-shim.min',
], function ($, Chart) {

    window.Bar = Class.create(Common, {

        renderChart: function (selector, dataset, label) {
            let charJsOptions= {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        suggestedMin: 5,
                        ticks: {
                            beginAtZero: true,
                            callback: function(value) {if (value % 1 === 0) {return value;}}
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        titleFont: {
                            size: 15
                        },
                        callbacks: {
                            title: function (context) {
                                return context[0].raw.tooltipTitle || context[0].label;
                            }
                        },
                        bodyFont: {
                            size: 15
                        },
                        footerFont: {
                            size: 15
                        }
                    }
                }
            }

            let charJsData = {
                datasets: [{
                    data: dataset,
                    label: label,
                    backgroundColor: '#f1d4b3',
                    borderColor: '#eb5202',
                    borderWidth: 1
                }]
            }

            let charJsPlugins = [];
            if (Math.max(...dataset.map(function (item) { return item.y })) === 0) {
                charJsPlugins[0] = {
                    beforeDraw: function (chart) {
                        let width = chart.width;
                        let height = chart.height;
                        let ctx = chart.ctx;

                        ctx.restore();
                        let fontSize = (height / 130).toFixed(2);
                        ctx.font = fontSize + "em sans-serif";
                        ctx.fillStyle = '#bbb7b7';
                        ctx.textBaseline = "middle";

                        let text = M2ePro.translator.translate('No Data');
                        let textX = Math.round((width - ctx.measureText(text).width) / 2);
                        let textY = height / 2;

                        ctx.fillText(text, textX, textY);
                        ctx.save();
                    }
                }
            }

            new Chart($(selector), {
                type: 'bar',
                data: charJsData,
                options: charJsOptions,
                plugins: charJsPlugins
            })
        },
    });
});
