define([
    'jquery',
    'jquery/validate',
    'mage/validation'
], function(jQuery) {

    jQuery.validator.prototype.showLabel = function (element, message) {
        var label = this.errorsFor(element),
            close = jQuery('<a />');

        close.on('click', function () {
            jQuery(this).parent().remove();
        });
        if (label.length) {
            // refresh error/success class
            label.removeClass(this.settings.validClass).addClass(this.settings.errorClass);

            // check if we have a generated label, replace the message then
            if (label.attr("generated")) {
                label.hide().html(message);
            }
        } else {
            var currentElement = jQuery(element);

            if (!element.visible()) {
                currentElement = currentElement.parent();
            }

            var MIN_WIDTH = 110,
                width = +currentElement.outerWidth();
            
            width < MIN_WIDTH && (width = MIN_WIDTH);

            // create label
            label = jQuery("<" + this.settings.errorElement + "/>")
                .attr({
                    "for": this.idOrName(element),
                    generated: true,
                    style: 'position: absolute;' +
                    'width: ' + width + 'px;' +
                    'left: ' + currentElement.position().left + 'px;' +
                    'z-index: 300'
                })
                .addClass(this.settings.errorClass)
                .html(message || "");

            if (this.settings.wrapper) {
                // make sure the element is visible, even in IE
                // actually showing the wrapped element is handled elsewhere
                label = label.hide().show().wrap("<" + this.settings.wrapper + "/>").parent();
            }
            if (!this.labelContainer.append(label).length) {
                if (this.settings.errorPlacement) {
                    this.settings.errorPlacement(label, jQuery(element));
                } else {
                    label.insertAfter(element);
                }
            }
        }
        if (!message && this.settings.success) {
            label.text("");
            if (typeof this.settings.success === "string") {
                label.addClass(this.settings.success);
            } else {
                this.settings.success(label, element);
            }
        }

        label.append(close);
        this.toShow = this.toShow.add(label);
    };

});