define([
        'jquery',
        'Magento_Ui/js/modal/confirm',
        'M2ePro/Plugin/Storage',
        'M2ePro/Common',
        'M2ePro/General/PhpFunctions'
],
function(jQuery, confirm, localStorage) {
    window.TemplateEdit = Class.create(Common, {

        // ---------------------------------------

        showConfirmMsg: true,
        skipSaveConfirmationPostFix: '_skip_save_confirmation',

        // ---------------------------------------

        getComponent: function()
        {
            alert('abstract getComponent');
        },

        // ---------------------------------------

        confirm: function(templateNick, confirmText, okCallback)
        {
            var self = this;
            var skipConfirmation = localStorage.get(this.getComponent()+'_template_'+templateNick+self.skipSaveConfirmationPostFix);

            if (!confirmText || skipConfirmation) {
                okCallback();
                return;
            }

            confirm({
                title: M2ePro.translator.translate('Save Policy'),
                content: confirmText + '<div class="admin__field admin__field-option" style="position: absolute; bottom: 43px; left: 28px;">' +
                '<input class="admin__control-checkbox" type="checkbox" id="do_not_show_again" name="do_not_show_again">&nbsp;' + '<label for="do_not_show_again" class="admin__field-label"><span>'+ M2ePro.translator.translate('Do not show any more') + '</span></label>' + '</div>',
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
                }],
                actions: {
                    confirm: function () {
                        if ($('do_not_show_again').checked) {
                            localStorage.set(self.getComponent()+'_template_'+templateNick+self.skipSaveConfirmationPostFix, 1);
                        }

                        okCallback();
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },

        // ---------------------------------------

        deleteClick: function()
        {
            Common.prototype.confirm({
                actions: {
                    confirm: function () {
                        setLocation(M2ePro.url.get('deleteAction'));
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },

        duplicateClick: function($super, $headId, chapter_when_duplicate_text)
        {
            this.showConfirmMsg = false;

            $super($headId, chapter_when_duplicate_text);
        },

        saveClick: function($super, url, confirmText, templateNick)
        {
            if (!this.isValidForm()) {
                return;
            }

            if (confirmText && this.showConfirmMsg) {
                this.confirm(templateNick, confirmText, function() { $super(url, true); });
                return;
            }

            $super(url, true);
        },

        saveAndEditClick: function($super, url, tabsId, confirmText, templateNick)
        {
            if (!this.isValidForm()) {
                return;
            }

            if (confirmText && this.showConfirmMsg) {
                this.confirm(templateNick, confirmText, function() { $super(url, tabsId, true); });
                return;
            }

            $super(url, tabsId, true)
        },

        // ---------------------------------------

        forgetSkipSaveConfirmation: function()
        {
            localStorage.removeAllByPostfix(this.skipSaveConfirmationPostFix);
        }

        // ---------------------------------------
    });
});