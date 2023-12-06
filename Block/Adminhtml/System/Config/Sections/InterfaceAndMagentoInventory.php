<?php

namespace Ess\M2ePro\Block\Adminhtml\System\Config\Sections;

class InterfaceAndMagentoInventory extends \Ess\M2ePro\Block\Adminhtml\System\Config\Sections
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Module\Configuration */
    private $moduleConfigurationHelper;
    /** @var \Ess\M2ePro\Helper\Module\ChangeTracker */
    private $changeTrackerHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Module\Configuration $moduleConfigurationHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\ChangeTracker $changeTrackerHelper,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->supportHelper = $supportHelper;
        $this->moduleConfigurationHelper = $moduleConfigurationHelper;
        $this->changeTrackerHelper = $changeTrackerHelper;
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $formHelpText = __(
            <<<TEXT
<p>Here you can provide global settings for the Module Interface, Inventory, Price, and Variational Product management.
Recommendations for the tracking direct database changes can also be found below.
Read the <a href="%link" target="_blank">article</a> for more
details.</p><br>
<p>Click <strong>Save Config</strong> if you make any changes.</p>
TEXT
            ,
            [
                'link' => $this
                    ->supportHelper
                    ->getDocumentationArticleUrl('display/eBayMagentoV6X/Global+Settings')
            ]
        );

        $form->addField(
            'interface_and_magento_inventory_help',
            self::HELP_BLOCK,
            [
                'no_collapse' => true,
                'no_hide' => true,
                'content' => $formHelpText,
            ]
        );

        $this->appendChangeTrackerFieldset($form);
        $this->appendDirectDatabaseChangesFieldset($form);
        $this->appendQuantityAndPriceFieldset($form);
        $this->appendVariationProductSettingFieldset($form);
        $this->appendInterfaceFieldset($form);

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

    private function appendChangeTrackerFieldset(\Magento\Framework\Data\Form $form): void
    {
        $fieldset = $form->addFieldset(
            'smart_tracker_fieldset',
            [
                'legend' => __('[Beta] Enhanced Inventory Tracker')
            ]
        );

        $helpText = __(
            <<<HELPTEXT
<p>An advanced mechanism designed to track inventory updates.
The Inventory Tracker guarantees efficient synchronization of the product price and quantity updates,
whether they are made through a direct data insert into a database, CSV import, or Magento core algorithms.</p>
HELPTEXT
        );

        $fieldset->addField(
            'smart_tracker_help_block',
            self::HELP_BLOCK,
            [
                'no_hide' => true,
                'content' => $helpText
            ]
        );

        $fieldset->addField(
            'smart_tracker_status',
            self::SELECT,
            [
                'name' => 'groups[smart_tracker][status]',
                'label' => __('Enabled'),
                'value' => $this->changeTrackerHelper->getStatus(),
                'values' => [
                    ['value' => 0, 'label' => __('No')],
                    ['value' => 1, 'label' => __('Yes')],
                ],
            ]
        );

        $fieldset->addField(
            'smart_tracker_run_interval',
            self::SELECT,
            [
                'name' => 'groups[smart_tracker][run_interval]',
                'label' => __('Run interval'),
                'value' => $this->changeTrackerHelper->getInterval(),
                'values' => [
                    ['value' => 15 * 60, 'label' => '15 min'],
                    ['value' => 30 * 60, 'label' => '30 min'],
                    ['value' => 60 * 60, 'label' => '60 min'],
                    ['value' => 2 * 60 * 60, 'label' => '2 hours'],
                    ['value' => 4 * 60 * 60, 'label' => '4 hours'],
                    ['value' => 12 * 60 * 60, 'label' => '12 hours'],
                    ['value' => 24 * 60 * 60, 'label' => '24 hours'],
                ],
            ]
        );
    }

    private function appendInterfaceFieldset(\Magento\Framework\Data\Form $form)
    {
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
                'label' => __('Products Thumbnail'),
                'values' => [
                    0 => __('Do Not Show'),
                    1 => __('Show'),
                ],
                'value' => $this->moduleConfigurationHelper->getViewShowProductsThumbnailsMode(),
                'tooltip' => __(
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
                'label' => __('Help Information'),
                'values' => [
                    0 => __('Do Not Show'),
                    1 => __('Show'),
                ],
                'value' => $this->moduleConfigurationHelper->getViewShowBlockNoticesMode(),
                'tooltip' => __(
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
                'content' => __('Restore All Helps & Remembered Choices'),
                'field_extra_attributes' => 'id="restore_block_notices_tr"',
            ]
        );
    }

    private function appendQuantityAndPriceFieldset(\Magento\Framework\Data\Form $form)
    {
        $fieldset = $form->addFieldset(
            'configuration_settings_magento_inventory_quantity',
            [
                'legend' => __('Quantity & Price'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'product_force_qty_mode',
            self::SELECT,
            [
                'name' => 'groups[quantity_and_price][fields][product_force_qty_mode][value]',
                'label' => __('Manage Stock "No", Backorders'),
                'values' => [
                    0 => __('Disallow'),
                    1 => __('Allow'),
                ],
                'value' => $this->moduleConfigurationHelper->isEnableProductForceQtyMode(),
                'tooltip' => __(
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
                'label' => __('Quantity To Be Listed'),
                'value' => $this->moduleConfigurationHelper->getProductForceQtyValue(),
                'tooltip' => __(
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
                'label' => __('Convert Magento Price Attribute'),
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'value' => $this->moduleConfigurationHelper
                    ->getMagentoAttributePriceTypeConvertingMode(),
                'tooltip' => __(
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
    }

    private function appendVariationProductSettingFieldset(\Magento\Framework\Data\Form $form)
    {
        $fieldset = $form->addFieldset(
            'magento_block_configuration_settings_variational_products_settings',
            [
                'legend' => __('Variational Product Settings'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'grouped_product_mode',
            self::SELECT,
            [
                'name' => 'groups[variational_product_settings][fields][grouped_product_mode][value]',
                'label' => __('List Grouped Product as'),
                'values' => [
                    1 => __('Product Set'),
                    0 => __('Variations'),
                ],
                'value' => $this->moduleConfigurationHelper->getGroupedProductMode(),
                'tooltip' => __(
                    <<<HTML
<b>Product Set</b> - a group of products will be listed as a Set (Individual Item).
Customers can purchase products only as a set. Read the <a href="%url" target="_blank">article</a> for details.
<b>Variations</b> - a group of products will be listed as a Variational Item.
Customers can purchase each option of Variational Product separately.
HTML
                    ,
                    [
                        'url' => 'https://help.m2epro.com/support/solutions/articles/9000218437'
                    ]
                ),
            ]
        );
    }

    private function appendDirectDatabaseChangesFieldset(\Magento\Framework\Data\Form $form)
    {
        $fieldset = $form->addFieldset(
            'direct_database_changes_field',
            [
                'legend' => __('Direct Database Changes'),
                'collabsable' => false,
            ]
        );

        $inspectorMode = $this->moduleConfigurationHelper->isEnableListingProductInspectorMode();

        $fieldset->addField(
            'listing_product_inspector_mode',
            self::SELECT,
            [
                'name' => 'groups[direct_database_changes][fields][listing_product_inspector_mode][value]',
                'label' => __('Track Direct Database Changes'),
                'values' => [
                    ['value' => 0, 'label' => __('No')],
                    ['value' => 1, 'label' => __('Yes')],
                ],
                'value' => $inspectorMode,
                'tooltip' => __(
                    <<<HTML
<p>If you update Magento Product information over the Magento Core Models (e.g. direct SQL injections),
 use one of the options below to make M2E Pro detect these changes:</p>
 <ul>
<li>M2E Pro Models (Object or Structural Methods). Read <a target="_blank" href="%url_1"> the article</a>
for more information.</li>
<li>M2E Pro plug-in for the Magmi Import tool. Learn the details <a target="_blank" href="%url_2">here</a>.</li>
<li>Track Direct Database Changes. Please note that this option is resource-consuming and may affect the
performance of your Magento site and synchronization with Channels.</li>
</ul>
HTML
                    ,
                    [
                        'url_1' => 'https://help.m2epro.com/support/solutions/articles/9000228224',
                        'url_2' => 'https://help.m2epro.com/support/solutions/articles/9000228276',
                    ]
                ),
            ]
        );
    }
}
