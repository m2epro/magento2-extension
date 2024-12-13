define([
    'jquery',
    'mage/translate',
    'M2ePro/Common'
], function($, $t) {
    const ComplianceDocuments = Class.create(Common, {
        tableWrapperSelector: '#documents_table_wrapper',
        addRowButtonSelector: '.add_row',
        removeRowButtonSelector: '.remove_row',

        maxTableRows: 10,

        initialize: function () {
            this.initValiadators();
            this.initObservers();
        },

        // -----------------------

        initObservers: function () {
            $(this.tableWrapperSelector).on('click', this.addRowButtonSelector, this.addRow.bind(this));
            $(this.tableWrapperSelector).on('click', this.removeRowButtonSelector, this.removeRow.bind(this));
        },

        initValiadators: function () {
            $.validator.addMethod('M2ePro-compliance-document-language-validator', function(value, el) {

                const typeValue = $(el).closest('tr').find('select[name*=document_type]').val()
                const urlValue = $(el).closest('tr').find('select[name*=document_attribute]').val();

                if (!typeValue || !urlValue) {
                    return true;
                }

                if (typeof value === 'string' ) {
                    value = value.trim();
                }

                return value.length > 0;
            }, $t('This is a required field.'));
        },

        // -----------------------

        addRow: function () {
            let clonedRow = $(this.tableWrapperSelector).find('tbody tr:last').clone();

            this.incrementNameAndIdsInRow(clonedRow);
            this.resetInputsInRow(clonedRow);

            clonedRow.find(this.removeRowButtonSelector).show();
            clonedRow.find('option[value="new-one-attribute"]').remove();
            clonedRow.find('label.mage-error').remove();

            $(this.tableWrapperSelector).find('tbody').append(clonedRow);

            this.validateRowsCount();

            window.initializationCustomAttributeInputs();
        },

        removeRow: function (event) {
            $(event.target).closest('tr').remove();
            this.validateRowsCount();
        },

        validateRowsCount: function () {
            $(this.tableWrapperSelector)
                    .find(this.addRowButtonSelector)
                    .attr('disabled', () => this.getTableRowsCount() >= this.maxTableRows);
        },

        getTableRowsCount: function () {
            return $(this.tableWrapperSelector).find('tbody tr').length;
        },

        incrementNameAndIdsInRow: function (row) {
            $(row).find('select, input')
                    .attr('name', (i, name) => name.replace(/(\d+)/, ($0, $1) => ++$1))
                    .attr('id', (i, id) => id.replace(/(\d+)/, ($0, $1) => ++$1));
        },

        resetInputsInRow: function (row) {
            $(row).find('option:selected')
                    .prop('selected', false)
                    .removeAttr('selected');
        },
    });

    return new ComplianceDocuments();
})
