define([], function () {
    window.ListingProductAdvancedFilterObj = Class.create({

        paramsKeys: [
            'rule_entity_id',
            'create_new_filter',
            'rule_updating',
            'creating_back',
            'updating_back',
            'clear_rule',
            'filter_name',
        ],

        fillGridReloadParams: function (gridReloadParams, ruleParams) {
            this.paramsKeys.forEach(function (key) {
                if (ruleParams.hasOwnProperty(key)) {
                    gridReloadParams[key] = ruleParams[key];
                }
            });
        },

        clearGridReloadParams: function (gridReloadParams) {
            this.paramsKeys.forEach(function (key) {
                if (gridReloadParams.hasOwnProperty(key)) {
                    delete gridReloadParams[key];
                }
            });
        },

        isNeedClearRuleForm: function (gridReloadParams) {
            if (!gridReloadParams.hasOwnProperty('clear_rule')) {
                return false;
            }

            return gridReloadParams.clear_rule === 'true';
        },

        clearRuleForm: function (gridReloadParams) {
            gridReloadParams.rule = '';
        },

        addClearRuleFormInput: function () {
            jQuery('<input>').attr({
                type: 'hidden',
                name: 'clear_rule',
                value: 'true',
            }).appendTo(this.getRuleForm());
        },

        addCreateNewFilterInput: function () {
            jQuery('<input>').attr({
                type: 'hidden',
                name: 'create_new_filter',
                value: 'true',
            }).appendTo(this.getRuleForm());
        },

        addUpdateFilterInput: function () {
            jQuery('<input>').attr({
                type: 'hidden',
                name: 'rule_updating',
                value: 'true',
            }).appendTo(this.getRuleForm());
        },

        addCreatingBackInput: function () {
            jQuery('<input>').attr({
                type: 'hidden',
                name: 'creating_back',
                value: 'true',
            }).appendTo(this.getRuleForm());
        },

        addUpdatingBackInput: function () {
            jQuery('<input>').attr({
                type: 'hidden',
                name: 'updating_back',
                value: 'true',
            }).appendTo(this.getRuleForm());
        },

        submitForm: function () {
            this.getRuleForm().trigger('submit')
        },

        getRuleForm: function () {
            return jQuery('#rule_form')
        },
    });
});
