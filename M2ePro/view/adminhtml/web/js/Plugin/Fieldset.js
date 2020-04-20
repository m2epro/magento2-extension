define([
    'jquery',
    'M2ePro/Plugin/Storage',
    "jquery/ui"
], function (jQuery, localStorage) {

    var PREFIX = 'm2e_bn_',
        storageWrapper = {
            set: function(key, value) {
                localStorage.set(PREFIX+key, value);
                return this;
            },
            get: function(key) {
                return localStorage.get(PREFIX+key);
            },
            remove: function(key) {
                localStorage.remove(PREFIX+key);
                return this;
            }
        };

    jQuery.widget('mage.collapsable', {
        options: {
            parent: null,
            openedClass: 'opened',
            wrapper: '.fieldset-wrapper'
        },

        _create: function () {
            this._events();
        },

        _events: function () {
            var self = this;

            this.element
                .on('show', function (e) {
                    var fieldsetWrapper = jQuery(this).closest(self.options.wrapper);
                    storageWrapper.set(this.id, 1);

                    fieldsetWrapper.addClass(self.options.openedClass);
                    e.stopPropagation();
                })
                .on('hide', function (e) {
                    var fieldsetWrapper = jQuery(this).closest(self.options.wrapper);
                    storageWrapper.set(this.id, 0);

                    fieldsetWrapper.removeClass(self.options.openedClass);
                    e.stopPropagation();
                });
        }
    });

    window.initCollapsable = function()
    {
        jQuery('.collapse').collapsable();
        jQuery.each(jQuery('.entry-edit'), function (i, entry) {
            jQuery('.collapse', entry).each(function () {
                var $self = jQuery(this),
                    isOpened = storageWrapper.get(this.id);

                if (isOpened === 0) {
                    $self.collapse('hide');
                    $self.data('collapsed', true);
                } else {
                    $self.collapse('show');
                    $self.data('collapsed', false);
                }
            });
        });
    }
});