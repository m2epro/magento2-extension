define([
    'jquery',
    'M2ePro/External/ChartJs/Chart.min',
    'M2ePro/External/ChartJs/es6-shim.min',
], function ($, Chart) {

    window.Bar = Class.create(Common, {

        renderWithData: function (selector, dataset, label) {

            new Chart($(selector), {
                type: 'bar',
                data: {
                    datasets: [{
                        data: dataset,
                        label: label,
                        backgroundColor: '#f1d4b3',
                        borderColor: '#eb5202',
                        borderWidth: 1
                    }]
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
                            }
                        }
                    }
                }
            })
        },

        renderWithoutData: function (selector, label) {

            new Chart($(selector), {
                type: 'bar',
                data: {
                    datasets: [{
                        data: [],
                        label: label,
                        backgroundColor: '#f1d4b3',
                        borderColor: '#eb5202',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                },
                plugins: [
                    {
                        beforeDraw: function (chart) {
                            var width = chart.width;
                            var height = chart.height;
                            var ctx = chart.ctx;

                            ctx.restore();
                            var fontSize = (height / 130).toFixed(2);
                            ctx.font = fontSize + "em sans-serif";
                            ctx.fillStyle = '#bbb7b7';
                            ctx.textBaseline = "middle";

                            var text = M2ePro.translator.translate('No Data');
                            var textX = Math.round((width - ctx.measureText(text).width) / 2);
                            var textY = height / 2;

                            ctx.fillText(text, textX, textY);
                            ctx.save();
                        }
                    }
                ]
            })
        },
    });
});
