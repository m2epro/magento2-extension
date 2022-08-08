<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs;

use \Ess\M2ePro\Helper\Component\Ebay\Configuration as ConfigurationHelper;

class Main extends \Ess\M2ePro\Block\Adminhtml\Settings\Tabs\AbstractTab
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Configuration */
    private $config;

    /** @var \Ess\M2ePro\Helper\View\Ebay */
    private $ebayViewHelper;

    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    private $attributeHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Configuration $config,
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Ess\M2ePro\Helper\Magento\Attribute $attributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->config = $config;
        $this->ebayViewHelper = $ebayViewHelper;
        $this->attributeHelper = $attributeHelper;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'method' => 'post',
                'action' => $this->getUrl('*/*/save')
            ]
        ]);

        $fieldset = $form->addFieldset(
            'images',
            [
                'legend' => $this->__('Images Uploading'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'upload_images_mode',
            'select',
            [
                'name'   => 'upload_images_mode',
                'label'  => $this->__('Main Image/Gallery Hosting Mode'),
                'values' => [
                    ConfigurationHelper::UPLOAD_IMAGES_MODE_AUTO => $this->__('Automatic'),
                    ConfigurationHelper::UPLOAD_IMAGES_MODE_SELF => $this->__('Self-Hosted'),
                    ConfigurationHelper::UPLOAD_IMAGES_MODE_EPS => $this->__('EPS-Hosted')
                ],
                'value' => $this->config->getUploadImagesMode(),
                'tooltip' => $this->__('
                    Select the Mode which you would like to use for uploading Images on eBay:<br/><br/>
                    <strong>Automatic</strong> — if you try to upload more then 1 Image for an Item or
                    separate Variational Attribute the EPS-hosted mode will be used automatically.
                    Otherwise, the Self-hosted mode will be used automatically;<br/>
                    <strong>Self-Hosted</strong> — all the Images are provided as a direct Links to the
                    Images saved in your Magento;<br/>
                    <strong>EPS-Hosted</strong> — the Images are uploaded to eBay EPS service.
                ')
            ]
        );

        $fieldset = $form->addFieldset(
            'additional',
            [
                'legend' => $this->__('Additional'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'prevent_item_duplicates_mode',
            'select',
            [
                'name'        => 'prevent_item_duplicates_mode',
                'label'       => $this->__('Prevent eBay Item Duplicates'),
                'values' => [
                    0 => $this->__('No'),
                    1 => $this->__('Yes'),
                ],
                'value' => $this->config->getPreventItemDuplicatesMode(),
                'tooltip' => $this->__(
                    'M2E Pro will not list Magento Product on the Channel if it is already listed
                    within the same eBay Account and Marketplace.'
                )
            ]
        );

        if ($this->ebayViewHelper->isFeedbacksShouldBeShown()) {
            $fieldset->addField(
                'feedback_notification_mode',
                'select',
                [
                    'name' => 'feedback_notification_mode',
                    'label' => $this->__('Negative Feedback'),
                    'values' => [
                        0 => $this->__('No'),
                        1 => $this->__('Yes')
                    ],
                    'value' => $this->config->getFeedbackNotificationMode(),
                    'tooltip' => $this->__('Show a notification in Magento when you receive negative Feedback on eBay.')
                ]
            );
        }

        $attributesTextType = $this->attributeHelper->filterAllAttrByInputTypes(['text']);

        $fieldset = $form->addFieldset(
            'identifiers',
            [
                'legend'  => $this->__('eBay Catalog Identifiers'),
                'collapsable' => false,
            ]
        );

        // UPC Settings
        $preparedAttributes = [];
        $warningToolTip = '';

        if ($this->config->isUpcModeCustomAttribute() &&
            !$this->attributeHelper->isExistInAttributesArray(
                $this->config->getUpcCustomAttribute(),
                $attributesTextType
            )) {

            $warningToolTip = $this->getAttributeWarningTooltip();
        }

        foreach ($attributesTextType as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if ($this->config->isUpcModeCustomAttribute() &&
                $this->config->getUpcCustomAttribute() == $attribute['code']) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => ConfigurationHelper::PRODUCT_IDENTIFIER_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'product_identifier_upc',
            self::SELECT,
            [
                'container_id'             => 'product_identifier_upc_tr',
                'label'                    => $this->__('UPC'),
                'name'                     => 'upc_mode',
                'values'                   => [
                    ConfigurationHelper::PRODUCT_IDENTIFIER_MODE_NONE           => $this->__('None'),
                    ConfigurationHelper::PRODUCT_IDENTIFIER_MODE_DOES_NOT_APPLY => $this->__('Does Not Apply'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => ['is_magento_attribute' => true]
                    ]
                ],
                'create_magento_attribute' => true,
                'value'                    => !$this->config->isUpcModeCustomAttribute()
                    ? $this->config->getProductIdMode('upc') : '',
                'tooltip'                  => $this->__('
                    Choose the Magento Attribute that contains the UPC for a Product or use a
                    "Does not apply" Option in case your Product does not have an UPC Value.<br/><br/>
                    The UPC or Universal Product Code is a 12 digit unique Identifier for a Product.
                '),
                'after_element_html'       => $warningToolTip
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'product_identifier_upc_custom_attribute',
            'hidden',
            [
                'name'  => 'upc_custom_attribute',
                'value' => $this->config->getUpcCustomAttribute(),
            ]
        );

        // EAN Settings
        $preparedAttributes = [];
        $warningToolTip = '';

        if ($this->config->isEanModeCustomAttribute() &&
            !$this->attributeHelper->isExistInAttributesArray(
                $this->config->getEanCustomAttribute(),
                $attributesTextType
            )) {
            $warningToolTip = $this->getAttributeWarningTooltip();
        }

        foreach ($attributesTextType as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if ($this->config->isEanModeCustomAttribute() &&
                $this->config->getEanCustomAttribute() == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => ConfigurationHelper::PRODUCT_IDENTIFIER_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'product_identifier_ean',
            self::SELECT,
            [
                'container_id'             => 'product_identifier_ean_tr',
                'label'                    => $this->__('EAN'),
                'name'                     => 'ean_mode',
                'values'                   => [
                    ConfigurationHelper::PRODUCT_IDENTIFIER_MODE_NONE           => $this->__('None'),
                    ConfigurationHelper::PRODUCT_IDENTIFIER_MODE_DOES_NOT_APPLY => $this->__('Does Not Apply'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value'                    => !$this->config->isEanModeCustomAttribute()
                    ? $this->config->getProductIdMode('ean') : '',
                'create_magento_attribute' => true,
                'tooltip'                  => $this->__('
                    Choose the Magento Attribute that contains the EAN for a Product or use a
                    "Does not apply" Option in case your Product does not have an EAN Value.<br/><br/>
                    The EAN or European Article Number, now renamed International Article Number, is
                    the 13 digit unique Identifier for a Product.
                '),
                'after_element_html'       => $warningToolTip
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'product_identifier_ean_custom_attribute',
            'hidden',
            [
                'name'  => 'ean_custom_attribute',
                'value' => $this->config->getEanCustomAttribute(),
            ]
        );

        // ISBN Settings
        $preparedAttributes = [];
        $warningToolTip = '';

        if ($this->config->isIsbnModeCustomAttribute() &&
            !$this->attributeHelper->isExistInAttributesArray(
                $this->config->getIsbnCustomAttribute(),
                $attributesTextType
            )) {
            $warningToolTip = $this->getAttributeWarningTooltip();
        }

        foreach ($attributesTextType as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if ($this->config->isIsbnModeCustomAttribute() &&
                $this->config->getIsbnCustomAttribute() == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => ConfigurationHelper::PRODUCT_IDENTIFIER_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'product_identifier_isbn',
            self::SELECT,
            [
                'container_id'             => 'product_identifier_isbn_tr',
                'label'                    => $this->__('ISBN'),
                'name'                     => 'isbn_mode',
                'values'                   => [
                    ConfigurationHelper::PRODUCT_IDENTIFIER_MODE_NONE           => $this->__('None'),
                    ConfigurationHelper::PRODUCT_IDENTIFIER_MODE_DOES_NOT_APPLY => $this->__('Does Not Apply'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => !$this->config->isIsbnModeCustomAttribute() ? $this->config->getProductIdMode('isbn') : '',
                'create_magento_attribute' => true,
                'tooltip'                  => $this->__('
                    Choose the Magento Attribute that contains the ISBN for a Product or use a
                    "Does not apply" Option in case your Product does not have an ISBN Value.<br/><br/>
                    The ISBN or International Standard Book Number is a unique Identifier for a book.
                    '),
                'after_element_html'       => $warningToolTip
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'product_identifier_isbn_custom_attribute',
            'hidden',
            [
                'name'  => 'isbn_custom_attribute',
                'value' => $this->config->getIsbnCustomAttribute(),
            ]
        );

        // EPID Settings
        $preparedAttributes = [];

        $warningToolTip = '';
        if ($this->config->isEpidModeCustomAttribute() &&
            !$this->attributeHelper->isExistInAttributesArray(
                $this->config->getEpidCustomAttribute(),
                $attributesTextType
            )) {
            $warningToolTip = $this->getAttributeWarningTooltip();
        }

        foreach ($attributesTextType as $attribute) {
            $attrs = ['attribute_code' => $attribute['code']];
            if ($this->config->isEpidModeCustomAttribute() &&
                $this->config->getEpidCustomAttribute() == $attribute['code']
            ) {
                $attrs['selected'] = 'selected';
            }
            $preparedAttributes[] = [
                'attrs' => $attrs,
                'value' => ConfigurationHelper::PRODUCT_IDENTIFIER_MODE_CUSTOM_ATTRIBUTE,
                'label' => $attribute['label'],
            ];
        }

        $fieldset->addField(
            'product_identifier_epid',
            self::SELECT,
            [
                'container_id'             => 'product_identifier_epid_tr',
                'label'                    => $this->__('ePID (Product Reference ID)'),
                'name'                     => 'epid_mode',
                'values'                   => [
                    ConfigurationHelper::PRODUCT_IDENTIFIER_MODE_NONE => $this->__('None'),
                    [
                        'label' => $this->__('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true
                        ]
                    ]
                ],
                'value' => !$this->config->isEpidModeCustomAttribute() ? $this->config->getProductIdMode('epid') : '',
                'create_magento_attribute' => true,
                'tooltip'                  => $this->__(
                    'An eBay Product ID is eBay\'s global reference ID for a Catalog Product.'
                ),
                'after_element_html'       => $warningToolTip
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text');

        $fieldset->addField(
            'product_identifier_epid_custom_attribute',
            'hidden',
            [
                'name'  => 'epid_custom_attribute',
                'value' =>  $this->config->getEpidCustomAttribute(),
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function getAttributeWarningTooltip()
    {
        $warningText = $this->__(
            <<<HTML
    Selected Magento Attribute is invalid.
    Please ensure that the Attribute exists in your Magento, has a relevant Input Type and it
    is included in all Attribute Sets.
    Otherwise, select a different Attribute from the drop-down.
HTML
        );

        return $this->__(
            <<<HTML
<span class="fix-magento-tooltip m2e-tooltip-grid-warning">
    {$this->getTooltipHtml($warningText)}
</span>
HTML
        );
    }

    protected function _beforeToHtml()
    {
        $this->jsUrl->add(
            $this->getUrl('*/ebay_settings/save'),
            \Ess\M2ePro\Block\Adminhtml\Ebay\Settings\Tabs::TAB_ID_MAIN
        );

        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants(ConfigurationHelper::class));

        $this->js->add(<<<JS
            require([
                'M2ePro/Ebay/Settings/Main'
            ], function(){
                window.EbaySettingsMainObj = new EbaySettingsMain();
                window.EbaySettingsMainObj.initObservers();
            });
JS
        );

        return parent::_beforeToHtml();
    }

    protected function getGlobalNotice()
    {
        return '';
    }
}
