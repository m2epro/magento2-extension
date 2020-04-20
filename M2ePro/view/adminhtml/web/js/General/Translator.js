define(['M2ePro/General/Common', 'underscore'], function(Common, _) {

    return Common.extend({
        translations: {},

        add: function(translations)
        {
            _.extend(this.translations, translations);
        },

        translate: function(text)
        {
            if (!this.translations[text]) {
                alert('Translation not found : "' + text + '"');
                throw new Error('Translation not found : "' + text + '"');
            }

            var textValue = this.translations[text],
                values = Array.prototype.slice.call(arguments).slice(1),
                placeholders = textValue.match(/%\w*%/g);

            if (!placeholders) {
                return textValue;
            }

            _.each(placeholders, function(placeholder) {
                var value = values.shift();

                if (!value) {
                    return;
                }
                textValue = textValue.replace(placeholder, value);
            });

            return textValue;
        }
    });

});