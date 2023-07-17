<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\Sections;

class InterfaceAndMagentoInventory extends \Ess\M2ePro\Block\Adminhtml\System\Config\Sections
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Module\Configuration */
    private $configurationHelper;

    /**
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Helper\Module\Configuration $configurationHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Module\Configuration $configurationHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->supportHelper = $supportHelper;
        $this->configurationHelper = $configurationHelper;
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $form->addField(
            'interface_and_magento_inventory_help',
            self::HELP_BLOCK,
            [
                'no_collapse' => true,
                'no_hide' => true,
                'content' => $this->__(
                    <<<HTML
<p>Here you can provide global settings for the Module Interface, Inventory, Price, and Variational Product management.
Recommendations for the tracking direct database changes can also be found below.
Read the <a href="%url%" target="_blank">article</a> for more
details.</p><br>
<p>Click <strong>Save Config</strong> if you make any changes.</p>
HTML
                    ,
                    $this->supportHelper->getDocumentationArticleUrl('display/eBayMagentoV6X/Global+Settings')
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'configuration_settings_interface',
            [
                'legend' => 'Interface',
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'view_show_products_thumbnails_mode',
            self::SELECT,
            [
                'name' => 'groups[interface][fields][view_show_products_thumbnails_mode][value]',
                'label' => $this->__('Products Thumbnail'),
                'values' => [
                    0 => $this->__('Do Not Show'),
                    1 => $this->__('Show'),
                ],
                'value' => $this->configurationHelper->getViewShowProductsThumbnailsMode(),
                'tooltip' => $this->__(
                    'Choose whether you want to see Thumbnail Images for Products on the
                    Add Products and View Listing Pages.'
                ),
            ]
        );

        $fieldset->addField(
            'view_show_block_notices_mode',
            self::SELECT,
            [
                'name' => 'groups[interface][fields][view_show_block_notices_mode][value]',
                'label' => $this->__('Help Information'),
                'values' => [
                    0 => $this->__('Do Not Show'),
                    1 => $this->__('Show'),
                ],
                'value' => $this->configurationHelper->getViewShowBlockNoticesMode(),
                'tooltip' => $this->__(
                    '<p>Choose whether you want the help information to be available at the top of
                    each M2E Pro Page.</p><br>
                    <p><strong>Please note</strong>, it does not disable the help-tips
                    (the icons with the additional information next to the main options).</p>'
                ),
            ]
        );

        $fieldset->addField(
            'restore_block_notices',
            self::BUTTON,
            [
                'label' => '',
                'content' => $this->__('Restore All Helps & Remembered Choices'),
                'field_extra_attributes' => 'id="restore_block_notices_tr"',
            ]
        );

        $fieldset = $form->addFieldset(
            'configuration_settings_magento_inventory_quantity',
            [
                'legend' => $this->__('Quantity & Price'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'product_force_qty_mode',
            self::SELECT,
            [
                'name' => 'groups[quantity_and_price][fields][product_force_qty_mode][value]',
                'label' => $this->__('Manage Stock "No", Backorders'),
                'values' => [
                    0 => $this->__('Disallow'),
                    1 => $this->__('Allow'),
                ],
                'value' => $this->configurationHelper->isEnableProductForceQtyMode(),
                'tooltip' => $this->__(
                    'Choose whether M2E Pro is allowed to List Products with unlimited stock or that are
                    temporarily out of stock.<br>
                    <b>Disallow</b> is the recommended setting for eBay Integration.'
                ),
            ]
        );

        $fieldset->addField(
            'product_force_qty_value',
            self::TEXT,
            [
                'name' => 'groups[quantity_and_price][fields][product_force_qty_value][value]',
                'label' => $this->__('Quantity To Be Listed'),
                'value' => $this->configurationHelper->getProductForceQtyValue(),
                'tooltip' => $this->__(
                    'Set a number to List, e.g. if you have Manage Stock "No" in Magento Product and set this Value
                    to 10, 10 will be sent as available Quantity to the Channel.'
                ),
                'field_extra_attributes' => 'id="product_force_qty_value_tr"',
                'class' => 'validate-greater-than-zero',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'magento_attribute_price_type_converting_mode',
            self::SELECT,
            [
                'name' => 'groups[quantity_and_price][fields][magento_attribute_price_type_converting_mode][value]',
                'label' => $this->__('Convert Magento Price Attribute'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value' => $this->configurationHelper
                    ->getMagentoAttributePriceTypeConvertingMode(),
                'tooltip' => $this->__(
                    '<p>Choose whether Magento Price Attribute values should be converted automatically.
                    With this option enabled, M2E Pro will provide currency conversion based on Magento
                    Currency Settings.</p>
                    <p><strong>For example</strong>, the Item Price is set to be taken from Magento Price
                    Attribute (e.g. 5 USD).<br>
                    If this Item is listed on Marketplace with a different Base Currency (e.g. GBP),
                    the currency conversion is performed automatically based on the set exchange rate
                    (e.g. 1 USD = 0.82 GBP).<br>
                    The Item will be available on Channel at the Price of 4.1 GBP.</p>'
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_configuration_settings_variational_products_settings',
            [
                'legend' => $this->__('Variational Product Settings'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'grouped_product_mode',
            self::SELECT,
            [
                'name' => 'groups[variational_product_settings][fields][grouped_product_mode][value]',
                'label' => $this->__('List Grouped Product as'),
                'values' => [
                    1 => $this->__('Product Set'),
                    0 => $this->__('Variations'),
                ],
                'value' => $this->configurationHelper->getGroupedProductMode(),
                'tooltip' => $this->__(
                    <<<HTML
<b>Product Set</b> - a group of products will be listed as a Set (Individual Item).
Customers can purchase products only as a set. Read the <a href="%url%" target="_blank">article</a> for details.
<b>Variations</b> - a group of products will be listed as a Variational Item.
Customers can purchase each option of Variational Product separately.
HTML
                    ,
                    $this->supportHelper->getSupportUrl(
                        '/support/solutions/articles/9000218437'
                    )
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'direct_database_changes_field',
            [
                'legend' => $this->__('Direct Database Changes'),
                'collabsable' => false,
            ]
        );

        $inspectorMode = $this->configurationHelper->isEnableListingProductInspectorMode();

        $fieldset->addField(
            'listing_product_inspector_mode',
            self::SELECT,
            [
                'name' => 'groups[direct_database_changes][fields][listing_product_inspector_mode][value]',
                'label' => $this->__('Track Direct Database Changes'),
                'values' => [
                    ['value' => 0, 'label' => $this->__('No')],
                    ['value' => 1, 'label' => $this->__('Yes')],
                ],
                'value' => $inspectorMode,
                'tooltip' => $this->__(
                    <<<HTML
<p>If you update Magento Product information over the Magento Core Models (e.g. direct SQL injections),
 use one of the options below to make M2E Pro detect these changes:</p>
 <ul>
<li>M2E Pro Models (Object or Structural Methods). Read <a target="_blank" href="%url1%"> the article</a>
for more information.</li>
<li>M2E Pro plug-in for the Magmi Import tool. Learn the details <a target="_blank" href="%url2%">here</a>.</li>
<li>Track Direct Database Changes. Please note that this option is resource-consuming and may affect the
performance of your Magento site and synchronization with Channels.</li>
</ul>
HTML
                    ,
                    $this->supportHelper->getSupportUrl('/support/solutions/articles/9000228224'),
                    $this->supportHelper->getSupportUrl('/support/solutions/articles/9000228276')
                ),
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $this->jsUrl->add(
            $this->getUrl('m2epro/settings_interfaceAndMagentoInventory/restoreRememberedChoices'),
            'settings_interface/restoreRememberedChoices'
        );

        $this->jsTranslator->add(
            'Help Blocks have been restored.',
            $this->__('Help Blocks have been restored.')
        );

        $this->js->addRequireJs(
            [
                'j' => 'jquery',
            ],
            <<<JS
$('view_show_block_notices_mode').observe('change', function() {
    if ($('view_show_block_notices_mode').value === '1') {
        $('restore_block_notices_tr').show();
    } else {
        $('restore_block_notices_tr').hide();
    }
}).simulate('change');

$('restore_block_notices').observe('click', function() {
    SettingsObj.restoreAllHelpsAndRememberedChoices();
});

$('product_force_qty_mode').observe('change', function() {
    if($('product_force_qty_mode').value === '1') {
        $('product_force_qty_value_tr').show();
    } else {
        $('product_force_qty_value_tr').hide();
    }
}).simulate('change');
JS
        );
    }
}
