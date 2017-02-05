define([
    'jquery',
    'M2ePro/Plugin/Storage',
    'M2ePro/Common'
], function (jQuery, localStorage) {

    window.BlockNotice = Class.create(Common, {

        // ---------------------------------------

        initializedBlocks: [],

        storageKeys: {
            prefix: 'm2e_bn_',
            shown: '_was_shown',
            closed: '_closed',
            hiddenContent: '_hidden_content',
            expandedContent: '_expanded_content',
        },

        getHashedStorage: function(id)
        {
            var hashedStorageKey = this.storageKeys.prefix + md5(id).substr(0, 10);
            var resultStorage = localStorage.get(hashedStorageKey);

            if (resultStorage === null) {
                return '';
            }

            return resultStorage;
        },

        setHashedStorage: function(id)
        {
            var hashedStorageKey = this.storageKeys.prefix + md5(id).substr(0, 10);
            localStorage.set(hashedStorageKey, 1);
        },

        deleteHashedStorage: function(id)
        {
            var hashedStorageKey = this.storageKeys.prefix + md5(id).substr(0, 10);

            localStorage.remove(hashedStorageKey);
            localStorage.remove(id);
        },

        deleteAllHashedStorage: function()
        {
            localStorage.removeAllByPrefix(this.storageKeys.prefix);
        },

        // ---------------------------------------

        showContent: function (id, storage) {

            id = id || '';
            if (id == '') {
                return false;
            }

            if (typeof storage === 'undefined') {
                storage = true;
            }

            var block = jQuery('#' + id);
            var hasTitle = block.find('.title').hasClass('title-expand');

            if (!block.is(':visible')) {
                block.show();
            }

            block.find('.content').show(0, this.updateFloatingHeader);

            if (hasTitle) {
                block.find('.title .icon').removeClass('icon-expand').addClass('icon-shrink');
                var onclick = block.find('.title a').attr('onclick');
                onclick = onclick.replace(/expandTitledContent/, 'shrinkTitledContent');
                block.find('.title a').attr('onclick', onclick);
            } else {
                block.find('.shrink').show();
                block.find('.expand').hide();
            }

            storage && this.deleteHashedStorage(id + this.storageKeys.hiddenContent);

            return true;
        },

        hideContent: function (id, storage) {

            id = id || '';
            if (id == '') {
                return false;
            }

            if (typeof storage === 'undefined') {
                storage = true;
            }

            var block = jQuery('#' + id);

            block.find('.content').hide(0, this.updateFloatingHeader);
            block.find('.shrink').hide();
            block.find('.expand').show();

            storage && this.setHashedStorage(id + this.storageKeys.hiddenContent);

            return true;
        },

        shrinkContent: function (id, storage) {
            var self = this;
            id = id || '';
            if (id == '') {
                return false;
            }

            if (typeof storage === 'undefined') {
                storage = true;
            }

            var block = jQuery('#' + id);

            block.find('.content').animate({
                height: 'toggle',
                opacity: 'toggle'
            }, 200, function() {
                block.find('.shrink').fadeOut(100, function() {
                    block.find('.expand').fadeIn(100, self.updateFloatingHeader);
                });
            });

            storage && this.deleteHashedStorage(id + this.storageKeys.expandedContent);
        },

        expandContent: function (id, storage) {
            var self = this;
            id = id || '';
            if (id == '') {
                return false;
            }

            if (typeof storage === 'undefined') {
                storage = true;
            }

            var block = jQuery('#' + id);

            block.find('.expand').fadeOut(100, function() {
                block.find('.shrink').fadeIn(100, function(){
                    block.find('.content').animate({
                        height: 'toggle',
                        opacity: 'toggle'
                    }, 200, self.updateFloatingHeader);
                });
            });

            storage && this.setHashedStorage(id + this.storageKeys.expandedContent);
        },

        shrinkTitledContent: function (id, storage) {
            var self = this;
            id = id || '';
            if (id == '') {
                return false;
            }

            if (typeof storage === 'undefined') {
                storage = true;
            }

            var block = jQuery('#' + id);

            block.find('.title .icon').removeClass('icon-shrink').addClass('icon-expand');

            block.find('.content').animate({
                height: 'toggle',
                opacity: 'toggle'
            }, 200, function() {
                var onclick = block.find('.title a').attr('onclick');
                onclick = onclick.replace(/shrinkTitledContent/, 'expandTitledContent');
                block.find('.title a').attr('onclick', onclick);
                block.find('.title a').attr('title', M2ePro.translator.translate('Expand'));

                self.updateFloatingHeader();
            });

            storage && this.deleteHashedStorage(id + this.storageKeys.expandedContent);
        },

        expandTitledContent: function (id, storage) {
            var self = this;
            id = id || '';
            if (id == '') {
                return false;
            }

            if (typeof storage === 'undefined') {
                storage = true;
            }

            var block = jQuery('#' + id);

            block.find('.title .icon').removeClass('icon-expand').addClass('icon-shrink');

            block.find('.content').animate({
                height: 'toggle',
                opacity: 'toggle'
            }, 200, function() {
                var onclick = block.find('.title a').attr('onclick');
                onclick = onclick.replace(/expandTitledContent/, 'shrinkTitledContent');
                block.find('.title a').attr('onclick', onclick);
                block.find('.title a').attr('title', M2ePro.translator.translate('Collapse'));

                self.updateFloatingHeader();
            });

            storage && this.setHashedStorage(id + this.storageKeys.expandedContent);
        },

        // ---------------------------------------

        showBlock: function (id) {
            id = id || '';
            if (id == '') {
                return false;
            }
            $(id).show();
            this.deleteHashedStorage(id + this.storageKeys.closed);
            return true;
        },

        hideBlock: function (id) {
            var self = this;

            self.confirm({
                actions: {
                    confirm: function () {
                        id = id || '';
                        if (id == '') {
                            return false;
                        }
                        var block = jQuery('#' + id);
                        block.hide(0, function(){
                            self.updateFloatingHeader();
                            block.remove();
                        });
                        self.setHashedStorage(id + self.storageKeys.closed);
                    },
                    cancel: function () {
                        return false;
                    }
                }
            });
        },

        // ---------------------------------------

        hideFieldsetIcon: function (id)
        {
            var fieldsetId = id.split('help_block_')[1];
            jQuery('#' + fieldsetId + '-wrapper').find('.m2epro-fieldset-tooltip').hide();
        },

        // ---------------------------------------

        init: function (reinit) {

            if (typeof reinit === 'undefined') {
                reinit = false;
            }

            var blockNotices = jQuery('.block_notices');

            if (!blockNotices.length) {
                return;
            }

            if (typeof BLOCK_NOTICES_SHOW !== 'undefined' && !BLOCK_NOTICES_SHOW) {
                blockNotices.hide();
                return;
            }

            var self = this;

            blockNotices.each(function(index, block){

                var id = block.id;

                if (self.initializedBlocks.indexOf(id) > -1) {
                    return;
                }

                !reinit && self.initializedBlocks.push(id);

                var noCollapse = jQuery(block).hasClass('no_collapse');

                if (noCollapse) {
                    self.showContent(id, false);
                    return;
                }

                var wasShown = self.getHashedStorage(id + self.storageKeys.shown);
                var tooltiped = jQuery(block).hasClass('tooltiped');
                var expanded = self.getHashedStorage(id + self.storageKeys.expandedContent);
                var closed = self.getHashedStorage(id + self.storageKeys.closed);

                if (!wasShown) {
                    self.showContent(id, false);
                    self.setHashedStorage(id + self.storageKeys.shown);
                    tooltiped && self.hideFieldsetIcon(id);
                } else if (tooltiped) {
                    jQuery(block).hide();
                } else if (closed) {
                    jQuery(block).remove();
                } else if (expanded) {
                    self.showContent(id, false);
                } else {
                    jQuery(block).show();
                }
            });
        },

        /**
         * Gives ability to reinitialize already initialized help block, when init() will be called
         * @param block
         */
        removeInitializedBlock: function (block)
        {
            var index = this.initializedBlocks.indexOf(block);
            if (index > -1) {
                this.initializedBlocks.splice(index, 1);
            }
        }

        // ---------------------------------------
    });
});