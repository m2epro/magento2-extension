define([
    'M2ePro/Common'
], function () {
    window.InvoicesAndShipments = Class.create(Common, {

        initialize: function () {},

        initObservers: function () {
            $('create_magento_shipment')
                    .observe('change', InvoicesAndShipmentsObj.create_magento_shipment_mode_change)
                    .simulate('change');
        },

        create_magento_shipment_mode_change: function () {
            var createMagentoShipmentModeFbaValue = $('create_magento_shipment_fba_orders');
            var createMagentoShipmentModeFbaContainer = $('create_magento_shipment_fba_orders_container');

            if (this.value == 0) {
                createMagentoShipmentModeFbaValue.value = 0;
            }
            createMagentoShipmentModeFbaContainer.toggle(this.value == 1);
        },
    })
})
