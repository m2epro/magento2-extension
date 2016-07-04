define(function () {

    window.ConfigurationComponents = Class.create(Common, {

        // ---------------------------------------

        componentsTitles: [],

        // ---------------------------------------

        initialize: function()
        {
            jQuery.validator.addMethod('M2ePro-check-default-component', function(value) {

                var componentDefault        = $('view_common_component_default').value.toLowerCase(),
                    componentsEnabledNumber = 0;

                $$('.M2ePro-component-other').each(function(el) {
                    if ($(el).value == 1) {
                        componentsEnabledNumber++;
                    }
                });

                if (componentsEnabledNumber <= 1) {
                    return true;
                }

                return $('component_' + componentDefault + '_mode') &&
                    $('component_' + componentDefault + '_mode').value == 1;
            }, M2ePro.translator.translate('Default Component should be enabled.'));
        },

        // ---------------------------------------

        component_mode_change: function()
        {
            var enabledComponents = 0;

            $$('.M2ePro-component-other').each(function(el) {
                if ($(el).value == 1) {
                    enabledComponents++;
                }
            });

            ComponentsObj.updateDefaultComponentSelect();

            if (enabledComponents >= 2) {
                $('view_common_component_default_tr').show();
            } else {

                var defaultComponent = M2ePro.php.constant('Ess_M2ePro_Helper_Component_Amazon::NICK');
                if ($('component_amazon_mode').value == 1) {
                    defaultComponent = M2ePro.php.constant('Ess_M2ePro_Helper_Component_Amazon::NICK');
                } else if ($('component_buy_mode').value == 1) {
                    defaultComponent = M2ePro.php.constant('Ess_M2ePro_Helper_Component_Buy::NICK');
                }

                $('view_common_component_default').value = defaultComponent;
                $('view_common_component_default_tr').hide();
            }
        },

        updateDefaultComponentSelect: function()
        {
            var self = this;

            var html       = '',
                selected   = '',

                components = [
                    M2ePro.php.constant('Ess_M2ePro_Helper_Component_Amazon::NICK'),
                    M2ePro.php.constant('Ess_M2ePro_Helper_Component_Buy::NICK')
                ];

            components.each(function(nick) {

                if ($('component_' + nick + '_mode').value == 1) {

                    $('view_common_component_default').value == nick
                        ? selected = ' selected="selected"'
                        : selected = '';

                    html += '<option value="' + nick + '"' + selected + '>' +
                        self.componentsTitles[nick] +
                        '</option>';
                }
            });

            $('view_common_component_default').innerHTML = html;
        }

        // ---------------------------------------
    });
});