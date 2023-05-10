define([
    'jquery',
    'M2ePro/External/ChartJs/Chart.min',
    'M2ePro/External/ChartJs/es6-shim.min',
], function ($, Chart) {

    window.Doughnut = Class.create(Common, {

        renderWithData: function (dataset) {

            var total = dataset.data.reduce(function (acc, val) {
                return acc + val;
            }, 0);

            new Chart($('#sales_products'), {
                type: 'doughnut',
                data: {
                    labels: dataset.labels,
                    datasets: [{
                        data: dataset.data,
                        backgroundColor: dataset.backgroundColor
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            titleFont: {
                                size: 15
                            },
                            bodyFont: {
                                size: 15
                            },
                            footerFont: {
                                size: 15
                            },
                            callbacks: {
                                afterLabel: function (tooltipItem) {
                                    var dataset = tooltipItem.dataset.data[tooltipItem.dataIndex];
                                    var percent = Math.round((dataset / total * 100));
                                    return '(' + percent + '%)';
                                }
                            },
                        }
                    }
                },
                plugins: [this._getChartPlugin(total)]
            })
        },

        renderWithoutData: function () {

            var text = M2ePro.translator.translate('No Data');

            new Chart($('#sales_products'), {
                type: 'doughnut',
                data: {
                    labels: [text],
                    datasets: [{
                        data: [1],
                        backgroundColor: '#a9b4b8'
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: false
                    }
                },
                plugins: [this._getChartPlugin(text)]
            })
        },

        _getChartPlugin: function (text) {
            return {
                beforeDraw: function (chart) {
                    var width = chart.width;
                    var height = chart.height;
                    var ctx = chart.ctx;

                    ctx.restore();

                    var fontSize = (height / 130).toFixed(2);
                    ctx.font = fontSize + "em sans-serif";
                    ctx.fillStyle = '#bbb7b7';
                    ctx.textBaseline = 'top';

                    var textX = Math.round((width - ctx.measureText(text).width) / 2);
                    var textY = height / 2;

                    ctx.fillText(text, textX, textY);
                    ctx.save();
                }
            }
        }

    });
});
