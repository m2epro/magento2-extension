define([
    'M2ePro/Amazon/Template/Edit',
    'Magento_Ui/js/modal/confirm'
], function () {

    window.AmazonTemplateSynchronization = Class.create(AmazonTemplateEdit, {

        // ---------------------------------------

        initialize: function()
        {
            this.setValidationCheckRepetitionValue('M2ePro-synchronization-tpl-title',
                                                    M2ePro.translator.translate('The specified Title is already used for other Policy. Policy Title must be unique.'),
                                                    'Template\\Synchronization', 'title', 'id',
                                                    M2ePro.formData.id,
                                                    M2ePro.php.constant('Ess_M2ePro_Helper_Component_Amazon::NICK'));

            jQuery.validator.addMethod('M2ePro-input-time', function(value) {
                return value.match(/^\d{2}:\d{2}$/g);
            }, M2ePro.translator.translate('Wrong time format string.'));

            jQuery.validator.addMethod('validate-qty', function(value, el) {

                if (!el.up('.admin__field').visible()) {
                    return true;
                }

                if (value.match(/[^\d]+/g)) {
                    return false;
                }

                if (value <= 0) {
                    return false;
                }

                return true;
            }, M2ePro.translator.translate('Wrong value. Only integer numbers.'));

            // ---------------------------------------
            jQuery.validator.addMethod('M2ePro-validate-conditions-between', function(value, el) {

                var minValue = $(el.id.replace('_max','')).value;

                if (!el.up('.admin__field').visible()) {
                    return true;
                }

                return parseInt(value) > parseInt(minValue);
            }, M2ePro.translator.translate('Must be greater than "Min".'));
            // ---------------------------------------

            // ---------------------------------------
            jQuery.validator.addMethod('M2ePro-validate-stop-relist-conditions-product-status', function(value, el) {

                if (AmazonTemplateSynchronizationObj.isRelistModeDisabled()) {
                    return true;
                }

                if ($('stop_status_disabled').value == 1 && $('relist_status_enabled').value == 0) {
                    return false;
                }

                return true;
            }, M2ePro.translator.translate('Inconsistent Settings in Relist and Stop Rules.'));

            jQuery.validator.addMethod('M2ePro-validate-stop-relist-conditions-stock-availability', function(value, el) {

                if (AmazonTemplateSynchronizationObj.isRelistModeDisabled()) {
                    return true;
                }

                if ($('stop_out_off_stock').value == 1 && $('relist_is_in_stock').value == 0) {
                    return false;
                }

                return true;
            }, M2ePro.translator.translate('Inconsistent Settings in Relist and Stop Rules.'));

            jQuery.validator.addMethod('M2ePro-validate-stop-relist-conditions-item-qty', function(value, el) {

                if (AmazonTemplateSynchronizationObj.isRelistModeDisabled()) {
                    return true;
                }

                var stopMaxQty = 0,
                    relistMinQty = 0;

                var qtyType = el.getAttribute('qty_type');

                switch (parseInt($('stop_qty_' + qtyType).value)) {

                    case M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::STOP_QTY_NONE'):
                        return true;
                        break;

                    case M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::STOP_QTY_LESS'):
                        stopMaxQty = parseInt($('stop_qty_' + qtyType + '_value').value);
                        break;

                    case M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::STOP_QTY_BETWEEN'):
                        stopMaxQty = parseInt($('stop_qty_' + qtyType + '_value_max').value);
                        break;
                }

                switch (parseInt($('relist_qty_' + qtyType).value)) {

                    case M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::RELIST_QTY_NONE'):
                        return false;
                        break;

                    case M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::RELIST_QTY_MORE'):
                    case M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::RELIST_QTY_BETWEEN'):
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

        initObservers: function()
        {
            //list
            $('list_mode').observe('change', AmazonTemplateSynchronizationObj.listMode_change).simulate('change');
            $('list_qty_magento').observe('change', AmazonTemplateSynchronizationObj.listQty_change).simulate('change');
            $('list_qty_calculated').observe('change', AmazonTemplateSynchronizationObj.listQty_change).simulate('change');
            $('list_advanced_rules_mode').observe('change', AmazonTemplateSynchronizationObj.listAdvancedRules_change).simulate('change');

            //relist
            $('relist_mode').observe('change', AmazonTemplateSynchronizationObj.relistMode_change).simulate('change');
            $('relist_qty_magento').observe('change', AmazonTemplateSynchronizationObj.relistQty_change).simulate('change');
            $('relist_qty_calculated').observe('change', AmazonTemplateSynchronizationObj.relistQty_change).simulate('change');
            $('relist_advanced_rules_mode').observe('change', AmazonTemplateSynchronizationObj.relistAdvancedRules_change).simulate('change');

            //revise
            $('revise_update_qty')
                .observe('change', AmazonTemplateSynchronizationObj.reviseQty_change)
                .simulate('change');

            $('revise_update_qty_max_applied_value_mode')
                .observe('change', AmazonTemplateSynchronizationObj.reviseQtyMaxAppliedValueMode_change)
                .simulate('change');

            $('revise_update_price').observe('change', AmazonTemplateSynchronizationObj.revisePrice_change)
                .simulate('change');

            $('revise_update_price_max_allowed_deviation_mode').observe('change', AmazonTemplateSynchronizationObj.revisePriceMaxAllowedDeviationMode_change)
                .simulate('change');

            //stop
            $('stop_qty_magento').observe('change', AmazonTemplateSynchronizationObj.stopQty_change).simulate('change');
            $('stop_qty_calculated').observe('change', AmazonTemplateSynchronizationObj.stopQty_change).simulate('change');
            $('stop_advanced_rules_mode').observe('change', AmazonTemplateSynchronizationObj.stopAdvancedRules_change).simulate('change');
        },

        // ---------------------------------------

        isRelistModeDisabled: function()
        {
            return $('relist_mode').value == 0;
        },

        // ---------------------------------------

        duplicateClick: function($super, $headId)
        {
            this.setValidationCheckRepetitionValue('M2ePro-synchronization-tpl-title',
                                                    M2ePro.translator.translate('The specified Title is already used for other Policy. Policy Title must be unique.'),
                                                    'Template\\Synchronization', 'title', '','',
                                                    M2ePro.php.constant('Ess_M2ePro_Helper_Component_Amazon::NICK'));

            $super($headId, M2ePro.translator.translate('Add Synchronization Policy'));
        },

        // ---------------------------------------

        stopQty_change: function()
        {
            var qtyType = this.getAttribute('qty_type');

            var valueContainer    = $('stop_qty_' + qtyType + '_value_container'),
                valueMaxContainer = $('stop_qty_' + qtyType + '_value_max_container'),
                item              = valueContainer.select('label span')[0];

            valueContainer.hide();
            valueMaxContainer.hide();

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::STOP_QTY_LESS') ||
                this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::STOP_QTY_MORE')) {
                item.innerHTML = M2ePro.translator.translate('Quantity');
                valueContainer.show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::STOP_QTY_BETWEEN')) {
                item.innerHTML = M2ePro.translator.translate('Min Quantity');
                valueContainer.show();
                valueMaxContainer.show();
            }
        },

        stopAdvancedRules_change: function()
        {
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

        listMode_change: function()
        {
            var rulesContainer         = $('magento_block_amazon_template_synchronization_list_rules'),
                advancedRulesContainer = $('magento_block_amazon_template_synchronization_list_advanced_filters');

            if ($('list_mode').value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::LIST_MODE_NONE')) {
                rulesContainer.hide();
                advancedRulesContainer.hide();
            } else if ($('list_mode').value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::LIST_MODE_YES')) {
                rulesContainer.show();
                advancedRulesContainer.show();
            } else {
                rulesContainer.hide();
                advancedRulesContainer.hide();
            }
        },

        listQty_change: function()
        {
            var qtyType = this.getAttribute('qty_type');

            var valueContainer    = $('list_qty_' + qtyType + '_value_container'),
                valueMaxContainer = $('list_qty_' + qtyType + '_value_max_container'),
                item              = valueContainer.select('label span')[0];

            valueContainer.hide();
            valueMaxContainer.hide();

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::LIST_QTY_LESS') ||
                this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::LIST_QTY_MORE')) {
                item.innerHTML = M2ePro.translator.translate('Quantity');
                valueContainer.show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::LIST_QTY_BETWEEN')) {
                item.innerHTML = M2ePro.translator.translate('Min Quantity');
                valueContainer.show();
                valueMaxContainer.show();
            }
        },

        listAdvancedRules_change: function()
        {
            var rulesContainer = $('list_advanced_rules_filters_container'),
                warningContainer = $('list_advanced_rules_filters_warning');

            rulesContainer.hide();
            warningContainer.hide();

            if (this.value == 1) {
                rulesContainer.show();
                warningContainer.show();
            }
        },

        // ---------------------------------------

        relistMode_change: function()
        {
            var rulesContainer         = $('magento_block_amazon_template_synchronization_relist_rules'),
                advancedRulesContainer = $('magento_block_amazon_template_synchronization_relist_advanced_filters'),
                userLockContainer      = $('relist_filter_user_lock_tr_container'),
                sendDataContainer      = $('relist_send_data_tr_container');

            if ($('relist_mode').value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::RELIST_MODE_NONE')) {
                userLockContainer.hide();
                sendDataContainer.hide();
                rulesContainer.hide();
                advancedRulesContainer.hide();
            } else if ($('relist_mode').value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::RELIST_MODE_YES')) {
                userLockContainer.show();
                sendDataContainer.show();
                rulesContainer.show();
                advancedRulesContainer.show();
            } else {
                userLockContainer.hide();
                sendDataContainer.hide();
                rulesContainer.hide();
                advancedRulesContainer.hide();
            }
        },

        relistQty_change: function()
        {
            var qtyType = this.getAttribute('qty_type');

            var valueContainer    = $('relist_qty_' + qtyType + '_value_container'),
                valueMaxContainer = $('relist_qty_' + qtyType + '_value_max_container'),
                item              = valueContainer.select('label span')[0];

            valueContainer.hide();
            valueMaxContainer.hide();

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::RELIST_QTY_LESS') ||
                this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::RELIST_QTY_MORE')) {
                item.innerHTML = M2ePro.translator.translate('Quantity');
                valueContainer.show();
            }

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::RELIST_QTY_BETWEEN')) {
                item.innerHTML = M2ePro.translator.translate('Min Quantity');
                valueContainer.show();
                valueMaxContainer.show();
            }
        },

        relistAdvancedRules_change: function()
        {
            var rulesContainer = $('relist_advanced_rules_filters_container'),
                warningContainer = $('relist_advanced_rules_filters_warning');

            rulesContainer.hide();
            warningContainer.hide();

            if (this.value == 1) {
                rulesContainer.show();
                warningContainer.show();
            }
        },

        // ---------------------------------------

        reviseQty_change: function()
        {
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::REVISE_UPDATE_QTY_YES')) {
                $('revise_update_qty_max_applied_value_mode_tr').show();
                $('revise_update_qty_max_applied_value_line_tr').show();
                $('revise_update_qty_max_applied_value_mode').simulate('change');
            } else {
                $('revise_update_qty_max_applied_value_mode_tr').hide();
                $('revise_update_qty_max_applied_value_line_tr').hide();
                $('revise_update_qty_max_applied_value_tr').hide();
                $('revise_update_qty_max_applied_value_mode').value = M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::REVISE_MAX_AFFECTED_QTY_MODE_OFF');
            }
        },

        reviseQtyMaxAppliedValueMode_change: function(event)
        {
            var self = AmazonTemplateSynchronizationObj;

            $('revise_update_qty_max_applied_value_tr').hide();

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::REVISE_MAX_AFFECTED_QTY_MODE_ON')) {
                $('revise_update_qty_max_applied_value_tr').show();
            } else if (!event.cancelable) {
                self.openReviseMaxAppliedQtyDisableConfirmationPopUp();
            }
        },

        openReviseMaxAppliedQtyDisableConfirmationPopUp: function()
        {
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

        reviseQtyMaxAppliedValueDisableCancel: function()
        {
            $('revise_update_qty_max_applied_value_mode').selectedIndex = 1;
            $('revise_update_qty_max_applied_value_mode').simulate('change');
        },

        reviseQtyMaxAppliedValueDisableConfirm: function()
        {
            $('revise_update_qty_max_applied_value_mode').selectedIndex = 0;
            $('revise_update_qty_max_applied_value_mode').simulate('change');
        },

        // ---------------------------------------

        revisePrice_change: function()
        {
            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::REVISE_UPDATE_PRICE_YES')) {
                $('revise_update_price_max_allowed_deviation_mode_tr').show();
                $('revise_update_price_max_allowed_deviation_tr').show();
                $('revise_update_price_line').show();
                $('revise_update_price_max_allowed_deviation_mode').simulate('change');
            } else {
                $('revise_update_price_max_allowed_deviation_mode_tr').hide();
                $('revise_update_price_max_allowed_deviation_tr').hide();
                $('revise_update_price_line').hide();
                $('revise_update_price_max_allowed_deviation_mode').value = M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::REVISE_MAX_ALLOWED_PRICE_DEVIATION_MODE_OFF');
            }
        },

        revisePriceMaxAllowedDeviationMode_change: function(event)
        {
            var self = AmazonTemplateSynchronizationObj;

            $('revise_update_price_max_allowed_deviation_tr').hide();

            if (this.value == M2ePro.php.constant('Ess_M2ePro_Model_Amazon_Template_Synchronization::REVISE_MAX_ALLOWED_PRICE_DEVIATION_MODE_ON')) {
                $('revise_update_price_max_allowed_deviation_tr').show();
            } else if (!event.cancelable) {
                self.openReviseMaxAllowedDeviationPriceDisableConfirmationPopUp();
            }
        },

        openReviseMaxAllowedDeviationPriceDisableConfirmationPopUp: function()
        {
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

        revisePriceMaxAllowedDeviationDisableCancel: function()
        {
            $('revise_update_price_max_allowed_deviation_mode').selectedIndex = 1;
            $('revise_update_price_max_allowed_deviation_mode').simulate('change');
        },

        revisePriceMaxAllowedDeviationDisableConfirm: function()
        {
            $('revise_update_price_max_allowed_deviation_mode').selectedIndex = 0;
            $('revise_update_price_max_allowed_deviation_mode').simulate('change');
        }

        // ---------------------------------------
    });
});