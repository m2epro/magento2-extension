define([
    'M2ePro/Common',
], function () {
    window.DashboardListingProductIssuesTable = Class.create(Common, {

        initObservers: function () {

            jQuery('#dashboard_listing_product_issues').find('.issues-row').click(function () {
                window.open(jQuery(this).data('url'));
            });

            jQuery('#dashboard_listing_product_issues_view_more').click(function () {
                window.open(jQuery(this).data('url'));
            });

        }
    })
});
