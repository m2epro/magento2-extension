define([
    'M2ePro/Common',
    'Magento_Ui/js/modal/confirm'
], function () {

    window.EbayTemplateSynchronization = Class.create(Common, {
        // ---------------------------------------

        initialize: function () {
            var self = this;
            jQuery.validator.addMethod('M2ePro-validate-qty', function (value, el) {

                if (self.isElementHiddenFromPage(el)) {
                    return true;
                }

                if (value.match(/[^\d]+/g) || value <= 0) {
                    return false;
                }

                return true;
            }, M2ePro.translator.translate('Wrong value. Only integer numbers.'));

            // ---------------------------------------
            jQuery.validator.addMethod('M2ePro-validate-conditions-between', function (value, el) {

                var minValue = $(el.id.replace('_max', '')).value;

                if (!el.up('.admin__field').visible()) {
                    return true;
                }

                return parseInt(value) > parseInt(minValue);
            }, M2ePro.translator.translate('Must be greater than "Min".'));
            // ---------------------------------------

            // ---------------------------------------
            jQuery.validator.addMethod('M2ePro-validate-stop-relist-conditions-product-status', function (value, el) {

                if (EbayTemplateSynchronizationObj.isRelistModeDisabled()) {
                    return true;
                }

                if (EbayTemplateSynchronizationObj.isStopModeDisabled()) {
                    return true;
                }

                if ($('stop_status_disabled').value == 1 && $('relist_status_enabled').value == 0) {
                    return false;
                }

                return true;
            }, M2ePro.translator.translate('Inconsistent Settings in Relist and Stop Rules.'));

            jQuery.validator.addMethod('M2ePro-validate-stop-relist-conditions-stock-availability', function (value, el) {

                if (EbayTemplateSynchronizationObj.isRelistModeDisabled()) {
                    return true;
                }

                if (EbayTemplateSynchronizationObj.isStopModeDisabled()) {
                    return true;
                }

                if ($('stop_out_off_stock').value == 1 && $('relist_is_in_stock').value == 0) {
                    return false;
                }

                return true;
            }, M2ePro.translator.translate('Inconsistent Settings in Relist and Stop Rules.'));

            jQuery.validator.addMethod('M2ePro-validate-stop-relist-conditions-item-qty', function (value, el) {

                if (EbayTemplateSynchronizationObj.isRelistModeDisabled()) {
                    return true;
                }

                if (EbayTemplateSynchronizationObj.isStopModeDisabled()) {
                    return true;
                }

                var stopMaxQty = 0,
                    relistMinQty = 0;

                var qtyType = el.getAttribute('qty_type');

                switch (parseInt($('stop_qty_' + qtyType).value)) {

                    case M2ePro.php.constant('Ess_M2ePro_Model_Template_Synchronization::QTY_MODE_NONE'):
                        return true;
                        break;

                    case M2ePro.php.constant('Ess_M2ePro_Model_Template_Synchronization::QTY_MODE_LESS'):
                        stopMaxQty = parseInt($('stop_qty_' + qtyType + '_value').value);
                        break;

                    case M2ePro.php.constant('Ess_M2ePro_Model_Template_Synchronization::QTY_MODE_BETWEEN'):
                        stopMaxQty = parseInt($('stop_qty_' + qtyType + '_value_max').value);
                        break;
                }

                switch (parseInt($('relist_qty_' + qtyType).value)) {

                    case M2ePro.php.constant('Ess_M2ePro_Model_Template_Synchronization::QTY_MODE_NONE'):
                        return false;
                        break;

                    case M2ePro.php.constant('Ess_M2ePro_Model_Template_Synchronization::QTY_MODE_MORE'):
                    case M2ePro.php.constant('Ess_M2ePro_Model_Template_Synchronization::QTY_MODE_BETWEEN'):
                        relistMinQty = parseInt($('relist_qty_' + qtyType + '_value').value);
                        break;
                }

                if (relistMinQty <= stopMaxQty) {
                    return false;
                }

                return true;
            }, M2ePro.translator.translate('Inconsistent Settings in Relist and Stop Rules.'));
            // ---------------------------------------
        },

        initObservers: function () {
            //list
            $('list_mode').observe('change', EbayTemplateSynchronizationObj.listMode_change).simulate('change');
            $('list_qty_magento').observe('change', EbayTemplateSynchronizationObj.listQty_change).simulate('change');
            $('list_qty_calculated').observe('change', EbayTemplateSynchronizationObj.listQty_change).simulate('change');
            $('list_advanced_rules_mode').observe('change', EbayTemplateSynchronizationObj.listAdvancedRules_change).simulate('change');

            //relist
            $('relist_mode').observe('change', EbayTemplateSynchronizationObj.relistMode_change).simulate('change');
            $('relist_qty_magento').observe('change', EbayTemplateSynchronizationObj.relistQty_change).simulate('change');
            $('relist_qty_calculated').observe('change', EbayTemplateSynchronizationObj.relistQty_change).simulate('change');
            $('relist_advanced_rules_mode').observe('change', EbayTemplateSynchronizationObj.relistAdvancedRules_change).simulate('change');

            //revise
            $('revise_update_qty').observe('change', EbayTemplateSynchronizationObj.reviseQty_change)
                .simulate('change');

            $('revise_update_qty_max_applied_value_mode').observe('change', EbayTemplateSynchronizationObj.reviseQtyMaxAppliedValueMode_change)
                .simulate('change');

            $('revise_update_price').observe('change', EbayTemplateSynchronizationObj.revisePrice_change)
                .simulate('change');

            $('revise_update_price_max_allowed_deviation_mode').observe('change', EbayTemplateSynchronizationObj.revisePriceMaxAllowedDeviationMode_change)
                .simulate('change');

            //stop
            $('stop_mode').observe('change', EbayTemplateSynchronizationObj.stopMode_change).simulate('change');
            $('stop_qty_magento').observe('change', EbayTemplateSynchronizationObj.stopQty_change).simulate('change');
            $('stop_qty_calculated').observe('change', EbayTemplateSynchronizationObj.stopQty_change).simulate('change');
            $('stop_advanced_rules_mode').observe('change', EbayTemplateSynchronizationObj.stopAdvancedRules_change).simulate('change');
        },

        // ---------------------------------------

        isRelistModeDisabled: function () {
            return $('relist_mode').value == 0;
        },

        isStopModeDisabled: function () {
            return $('stop_mode').value == 0;
        },

        // ---------------------------------------

        listMode_change: function ()
        {
            var rulesContainer         = $('magento_block_ebay_template_synchronization_form_data_list_rules'),
                advancedRulesContainer = $('magento_block_ebay_template_synchronization_list_advanced_filters');

            rulesContainer.hide();
            advancedRulesContainer.hide();

            if (this.value == 1) {
                rulesContainer.show();
                advancedRulesContainer.show();
            }
        },

        listQty_change: function () {
            var qtyType = this.getAttribute('qty_type');

            var valueContainer = $('list_qty_' + qtyType + '_value_container'),
                valueMaxContainer = $('list_qty_' + qtyType + '_value_max_container'),
                item = valueContainer.select('label span')[0];

            valueContainer.hide();
            valueMaxContainer.hide();

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_Synchronization::QTY_MODE_LESS') ||
                this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_Synchronization::QTY_MODE_MORE')) {
                item.innerHTML = M2ePro.translator.translate('Quantity');
                valueContainer.show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_Synchronization::QTY_MODE_BETWEEN')) {
                item.innerHTML = M2ePro.translator.translate('Min Quantity');
                valueContainer.show();
                valueMaxContainer.show();
            }
        },

        // ---------------------------------------

        reviseQty_change: function () {
            if (this.value == 1) {
                $('revise_update_qty_max_applied_value_mode_tr').show();
                $('revise_update_qty_max_applied_value_line_tr').show();
                $('revise_update_qty_max_applied_value_mode').simulate('change');
            } else {
                $('revise_update_qty_max_applied_value_mode_tr').hide();
                $('revise_update_qty_max_applied_value_line_tr').hide();
                $('revise_update_qty_max_applied_value_tr').hide();
                $('revise_update_qty_max_applied_value_mode').value = 0;
            }
        },

        reviseQtyMaxAppliedValueMode_change: function (event) {
            var self = EbayTemplateSynchronizationObj;

            $('revise_update_qty_max_applied_value_tr').hide();

            if (this.value == 1) {
                $('revise_update_qty_max_applied_value_tr').show();
            } else if (!event.cancelable) {
                self.openReviseMaxAppliedQtyDisableConfirmationPopUp();
            }
        },

        openReviseMaxAppliedQtyDisableConfirmationPopUp: function () {
            var self = this;

            var element = jQuery('#revise_qty_max_applied_value_confirmation_popup_template').clone();

            element.confirm({
                title: M2ePro.translator.translate('Are you sure?'),
                actions: {
                    confirm: self.reviseQtyMaxAppliedValueDisableConfirm,
                    cancel: self.reviseQtyMaxAppliedValueDisableCancel
                },
                buttons: [{
                    text: M2ePro.translator.translate('Cancel'),
                    class: 'action-secondary action-dismiss',
                    click: function (event) {
                        this.closeModal(event);
                    }
                }, {
                    text: M2ePro.translator.translate('Confirm'),
                    class: 'action-primary action-accept',
                    click: function (event) {
                        this.closeModal(event, true);
                    }
                }]
            });
        },

        reviseQtyMaxAppliedValueDisableCancel: function () {
            $('revise_update_qty_max_applied_value_mode').selectedIndex = 1;
            $('revise_update_qty_max_applied_value_mode').simulate('change');
        },

        reviseQtyMaxAppliedValueDisableConfirm: function () {
            $('revise_update_qty_max_applied_value_mode').selectedIndex = 0;
            $('revise_update_qty_max_applied_value_mode').simulate('change');
        },

        // ---------------------------------------

        revisePrice_change: function () {
            if (this.value == 1) {
                $('revise_update_price_max_allowed_deviation_mode_tr').show();
                $('revise_update_price_max_allowed_deviation_tr').show();
                $('revise_update_price_line').show();
                $('revise_update_price_max_allowed_deviation_mode').simulate('change');
            } else {
                $('revise_update_price_max_allowed_deviation_mode_tr').hide();
                $('revise_update_price_max_allowed_deviation_tr').hide();
                $('revise_update_price_line').hide();
                $('revise_update_price_max_allowed_deviation_mode').value = 0;
            }
        },

        revisePriceMaxAllowedDeviationMode_change: function (event) {
            var self = EbayTemplateSynchronizationObj;

            $('revise_update_price_max_allowed_deviation_tr').hide();

            if (this.value == 1) {
                $('revise_update_price_max_allowed_deviation_tr').show();
            } else if (!event.cancelable) {
                self.openReviseMaxAllowedDeviationPriceDisableConfirmationPopUp();
            }
        },

        openReviseMaxAllowedDeviationPriceDisableConfirmationPopUp: function () {
            var self = this;

            var element = jQuery('#revise_price_max_max_allowed_deviation_confirmation_popup_template').clone();

            element.confirm({
                title: M2ePro.translator.translate('Are you sure?'),
                actions: {
                    confirm: self.revisePriceMaxAllowedDeviationDisableConfirm,
                    cancel: self.revisePriceMaxAllowedDeviationDisableCancel
                },
                buttons: [{
                    text: M2ePro.translator.translate('Cancel'),
                    class: 'action-secondary action-dismiss',
                    click: function (event) {
                        this.closeModal(event);
                    }
                }, {
                    text: M2ePro.translator.translate('Confirm'),
                    class: 'action-primary action-accept',
                    click: function (event) {
                        this.closeModal(event, true);
                    }
                }]
            });
        },

        revisePriceMaxAllowedDeviationDisableCancel: function () {
            $('revise_update_price_max_allowed_deviation_mode').selectedIndex = 1;
            $('revise_update_price_max_allowed_deviation_mode').simulate('change');
        },

        revisePriceMaxAllowedDeviationDisableConfirm: function () {
            $('revise_update_price_max_allowed_deviation_mode').selectedIndex = 0;
            $('revise_update_price_max_allowed_deviation_mode').simulate('change');
        },

        // ---------------------------------------

        relistMode_change: function ()
        {
            var rulesContainer = $('magento_block_ebay_template_synchronization_form_data_relist_rules'),
                advancedRulesContainer = $('magento_block_ebay_template_synchronization_relist_advanced_filters'),
                userLockContainer = $('relist_filter_user_lock_tr_container');

            userLockContainer.hide();
            rulesContainer.hide();
            advancedRulesContainer.hide();

            if (this.value == 1) {
                userLockContainer.show();
                rulesContainer.show();
                advancedRulesContainer.show();
            }
        },

        relistQty_change: function () {
            var qtyType = this.getAttribute('qty_type');

            var valueContainer = $('relist_qty_' + qtyType + '_value_container'),
                valueMaxContainer = $('relist_qty_' + qtyType + '_value_max_container'),
                item = valueContainer.select('label span')[0];

            valueContainer.hide();
            valueMaxContainer.hide();

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_Synchronization::QTY_MODE_LESS') ||
                this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_Synchronization::QTY_MODE_MORE')) {
                item.innerHTML = M2ePro.translator.translate('Quantity');
                valueContainer.show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_Synchronization::QTY_MODE_BETWEEN')) {
                item.innerHTML = M2ePro.translator.translate('Min Quantity');
                valueContainer.show();
                valueMaxContainer.show();
            }
        },


        // ---------------------------------------

        stopQty_change: function () {
            var qtyType = this.getAttribute('qty_type');

            var valueContainer = $('stop_qty_' + qtyType + '_value_container'),
                valueMaxContainer = $('stop_qty_' + qtyType + '_value_max_container'),
                item = valueContainer.select('label span')[0];

            valueContainer.hide();
            valueMaxContainer.hide();

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_Synchronization::QTY_MODE_LESS') ||
                this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_Synchronization::QTY_MODE_MORE')) {
                item.innerHTML = M2ePro.translator.translate('Quantity');
                valueContainer.show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Template_Synchronization::QTY_MODE_BETWEEN')) {
                item.innerHTML = M2ePro.translator.translate('Min Quantity');
                valueContainer.show();
                valueMaxContainer.show();
            }
        },

        // ---------------------------------------

        stopMode_change: function ()
        {
            var rulesContainer         = $('magento_block_ebay_template_synchronization_stop_rules'),
                advancedRulesContainer = $('magento_block_ebay_template_synchronization_stop_advanced_filters');

            rulesContainer.hide();
            advancedRulesContainer.hide();

            if ($('stop_mode').value == 1) {
                rulesContainer.show();
                advancedRulesContainer.show();
            }
        },

        // ---------------------------------------

        listAdvancedRules_change: function () {
            var rulesContainer = $('list_advanced_rules_filters_container'),
                warningContainer = $('list_advanced_rules_filters_warning');

            rulesContainer.hide();
            warningContainer.hide();

            if (this.value == 1) {
                rulesContainer.show();
                warningContainer.show();
            }
        },

        relistAdvancedRules_change: function () {
            var rulesContainer = $('relist_advanced_rules_filters_container'),
                warningContainer = $('relist_advanced_rules_filters_warning');

            rulesContainer.hide();
            warningContainer.hide();

            if (this.value == 1) {
                rulesContainer.show();
                warningContainer.show();
            }
        },

        stopAdvancedRules_change: function () {
            var rulesContainer = $('stop_advanced_rules_filters_container'),
                warningContainer = $('stop_advanced_rules_filters_warning');

            rulesContainer.hide();
            warningContainer.hide();

            if (this.value == 1) {
                rulesContainer.show();
                warningContainer.show();
            }
        },

        // ---------------------------------------
    });
});