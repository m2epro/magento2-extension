define([
    'M2ePro/Common',
], function () {
    window.DashboardSalesDateSwitcher = Class.create(Common, {

        initObservers: function (url) {
            $('dashboard_chart_period').on('change', function () {
                window.location = url + '/' + this.value;
            })
        }
    })
});
