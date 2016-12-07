<?php

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Account\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class Repricing extends AbstractForm
{
    protected function _prepareForm()
    {
        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');

        /** @var \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper */
        $magentoAttributeHelper = $this->getHelper('Magento\Attribute');

        $generalAttributes = $magentoAttributeHelper->getGeneralFromAllAttributeSets();

        $attributesByInputTypes = array(
            'text_price' => $magentoAttributeHelper->filterByInputTypes($generalAttributes, array('text', 'price')),
            'boolean' => $magentoAttributeHelper->filterByInputTypes($generalAttributes, array('boolean')),
        );

        $form = $this->_formFactory->create();

        $form->addField(
            'amazon_accounts_repricing',
            self::HELP_BLOCK,
            [
                'content' => $this->__(<<<HTML
    You can use a <strong>Repricing Tool</strong> developed by M2E Pro Team. This Tool allows to improve the position
    of your offer.
    It will <strong>review</strong> your Competitors' pricing and <strong>change</strong>
    your Product prices accordingly.<br/><br/>

    To start working with the Tool you should Add the information about your M2E Pro Amazon Account to the Repricing
    Service and Link it
    by clicking on the Button <strong>Link Now</strong>. Futher, you will need to complete the Wizard which allows
    Setting Up all necessary data.<br/><br/>

    After your Account is successfully Linked, you will be able to review the information about the Products
    which are automatically
    managed by the Repricer. Also, <strong>Additional Options</strong> of Automatic Product Adding and
    Editing become available.
    To use them you should switch your Listing to the Seller Central View Mode and select the appropriate
    Option in the Actions bulk
    at the top of the Grid.<br/><br/>

    In case you decide to <strong>Stop</strong> using the Repricing Tool for M2E Pro Listings,
    you can click on <strong>Unlink</strong> Button.
    It will break the Connection between your M2E Pro Amazon Account and Repricing Service.<br/><br/>
    More detailed information on how to work with the Repricing Service you can find
    <a href="%url%" target="_blank" class="external-link">here</a>
HTML
                , $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/bwAkAQ'))
            ]
        );

        $fieldset = $form->addFieldset(
            'general',
            [
                'legend' => $this->__('General'),
                'collapsable' => false
            ]
        );

        $isRepricingLinked = $account->getChildObject()->isRepricing();

        if ($isRepricingLinked) {

            $fieldset->addField('unlink_repricing',
                'note',
                [
                    'text' => <<<HTML
    <span>{$this->__('This Amazon Account is successfully linked with Amazon Repricing Tool')}</span>&nbsp;
    <button type="button" class="action primary" onclick="AmazonAccountObj.unlinkRepricing()">
        {$this->__('Unlink')}
    </button>
HTML
                    ,
                    'style' => 'text-align: center;'
                ]
            );

            /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing $repricing */
            $repricing = $account->getChildObject()->getRepricing();

            $fieldset->addField('customer',
                self::CUSTOM_CONTAINER,
                [
                    'label' => $this->__('Customer'),
                    'style' => '',
                    'text' => <<<HTML
    <span id="repricing_email">{$repricing->getEmail()}</span>&nbsp;
    <br/>
    <a href="javascript:void(0)" onclick="AmazonAccountObj.openManagement()">
        {$this->__('Go to Amazon Repricing Tool')}
    </a>
HTML
                    ,
                    'field_extra_attributes' => '',
                ]
            );

            $m2eProRepricingProducts = $this->getRepricingProductsCount();

            $fieldset = $form->addFieldset(
                'statistic',
                [
                    'legend' => $this->__('Statistic'),
                    'collapsable' => false
                ]
            );

            $fieldset->addField('repricing_products',
                self::CUSTOM_CONTAINER,
                [
                    'label' => $this->__('Repricing Products'),
                    'style' => '',
                    'text' => <<<HTML
    <span id="repricing_total_products">{$repricing->getTotalProducts()}</span>
HTML
                    ,
                    'tooltip' => $this->__(
                        'This is a total number of Products managed by the Repricing Tool Linked to your
                        M2E Pro Amazon Account.'
                    ),
                    'field_extra_attributes' => '',
                ]
            );

            $fieldset->addField('m2epro_products',
                self::CUSTOM_CONTAINER,
                [
                    'label' => $this->__('M2E Pro Products'),
                    'style' => '',
                    'text' => <<<HTML
    <span id="m2epro_repricing_total_products">{$m2eProRepricingProducts}</span>
HTML
                    ,
                    'tooltip' => $this->__(
                        'This is a number of Products managed by the Repricing Tool in your M2E Pro.'
                    ),
                    'field_extra_attributes' => '',
                ]
            );

            if ($m2eProRepricingProducts > 0) {

                $fieldset = $form->addFieldset(
                    'additional_settings',
                    [
                        'legend' => $this->__('Additional Settings'),
                        'collapsable' => false
                    ]
                );

                // Regular price
                // --------------------------

                $fieldset->addField(
                    'regular_price_attribute',
                    'hidden',
                    [
                        'name' => 'repricing[regular_price_attribute]',
                        'value' => $repricing->getData('regular_price_attribute')
                    ]
                );

                $preparedAttributes = [];

                $priceModeAttribute = \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE;

                if ($repricing->getRegularPriceMode() == $priceModeAttribute &&
                    !$magentoAttributeHelper->isExistInAttributesArray(
                        $repricing->getData('regular_price_attribute'), $attributesByInputTypes['text_price']
                    ) && $repricing->getData('regular_price_attribute') != '') {

                    $attrs = [
                        'attribute_code' => $repricing->getData('regular_price_attribute'),
                        'selected' => 'selected'
                    ];

                    $preparedAttributes[] = [
                        'attrs' => $attrs,
                        'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE,
                        'label' => $magentoAttributeHelper->getAttributeLabel(
                            $repricing->getData('regular_price_attribute')
                        ),
                    ];
                }

                foreach ($attributesByInputTypes['text_price'] as $attribute) {
                    $attrs = ['attribute_code' => $attribute['code']];
                    if (
                        $repricing->getRegularPriceMode() == $priceModeAttribute
                        && $attribute['code'] == $repricing->getData('regular_price_attribute')
                    ) {
                        $attrs['selected'] = 'selected';
                    }
                    $preparedAttributes[] = [
                        'attrs' => $attrs,
                        'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE,
                        'label' => $attribute['label'],
                    ];
                }

                $priceCoefficient = $this->elementFactory->create('text', ['data' => [
                    'html_id' => 'regular_price_coefficient',
                    'name' => 'repricing[regular_price_coefficient]',
                    'label' => '',
                    'value' => $repricing->getData('regular_price_coefficient'),
                    'class' => 'M2ePro-validate-price-coefficient',
                ]]);
                $priceCoefficient->setForm($form);

                $fieldTooltip = '<div class="fix-magento-tooltip" style="margin-left: 20px; margin-right: 20px;">' .
                    $this->getTooltipHtml(
                        $this->__('
    Regular Price is a common Price without any changes. This value is used for Repricing Rules configuration
    and plays the role of the roll-back Price in case the Rules cannot be applied or the Goals provided
                    in the Repricing Rules cannot be achieved. You can find more detailed information about it
    <a href="%url1%" target="_blank" class="external-link">here</a>.<br/><br/>
    You can select the configurations for automatic updating of the Regular Price value
    by selecting from these options:<br/>
    <strong>Manually</strong> - means that the according value will be manually provided for the Products;<br/>
    <strong>According to Selling Format Policy settings</strong> - means that the Price value will
    be taken based on the Selling Format Policy settings, which is used for this Item in the Listing;<br/>
    <strong>From Product Price</strong> - means that the Price value from Magento Product Price will be taken;<br/>
    <strong>From Special Price</strong> - means that the Price value from Magento Special Price will be taken;<br/>
    <strong>From Magento Attribute</strong> - means that the Price value will
    be taken from the selected Attribute.<br/><br/>

    Please note, only common (available in all Attribute sets in your Magento),
    Text or Price field Attributes are available for selection.<br/><br/>

    More detailed information on how to work with this option you can find
    <a href="%url2%" target="_blank" class="external-link">here</a>.',
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/igAkAQ'),
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/JQMkAQ'))
                    ) .
                '</div>';

                $tooltipPriceCoefficient = '<div class="fix-magento-tooltip" style="margin-left: 20px;">' .
                    $this->getTooltipHtml(
                        $this->__('Absolute figure (+8,-3), percentage (+15%, -20%) or Currency rate (1.44)')
                    ) .
                '</div>';

                $value = (
                    $repricing->getRegularPriceMode() ==
                    \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE
                ) ? '' : $repricing->getRegularPriceMode();

                $fieldset->addField(
                    'regular_price_mode',
                    self::SELECT,
                    [
                        'name' => 'repricing[regular_price_mode]',
                        'label' => $this->__('Update Regular Price'),
                        'values' => [
                            \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_MANUAL => $this->__('Manually'),
                            \Ess\M2ePro\Model\Amazon\Account\Repricing::REGULAR_PRICE_MODE_PRODUCT_POLICY =>
                                $this->__('According to Selling Format Policy'),
                            \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_PRODUCT =>
                                $this->__('From Product Price'),
                            \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_SPECIAL =>
                                $this->__('From Special Price'),
                            [
                                'label' => $this->__('Magento Attributes'),
                                'value' => $preparedAttributes,
                                'attrs' => [
                                    'is_magento_attribute' => true
                                ]
                            ]
                        ],
                        'create_magento_attribute' => true,
                        'value' => $value,
                        'css_class' => 'price_mode',
                        'after_element_html' => $fieldTooltip . '<span id="regular_price_coefficient_td">' .
                            $priceCoefficient->toHtml() . $tooltipPriceCoefficient .
                        '</span>'
                    ]
                )->addCustomAttribute('allowed_attribute_types', 'text,price');

                $fieldset->addField('regular_price_variation_mode',
                    self::SELECT,
                    [
                        'container_id' => 'regular_price_variation_mode_tr',
                        'label' => $this->__('Regular Variation Price Source'),
                        'class' => 'select-main',
                        'name' => 'repricing[regular_price_variation_mode]',
                        'values' => [
                            \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_VARIATION_MODE_PARENT =>
                                $this->__('Main Product'),
                            \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_VARIATION_MODE_CHILDREN =>
                                $this->__('Associated Products')
                        ],
                        'value' => $repricing->getRegularPriceVariationMode(),
                        'tooltip' => $this->__(
                            'Determines where the Price for Bundle Products Options should be taken from.'
                        ),
                        'field_extra_attributes' => 'style="display: none;"'
                    ]
                );

                // Min Price
                // --------------------------

                $fieldset->addField(
                    'min_price_attribute',
                    'hidden',
                    [
                        'name' => 'repricing[min_price_attribute]',
                        'value' => $repricing->getData('min_price_attribute')
                    ]
                );

                $preparedAttributes = [];

                if ($repricing->getMinPriceMode() == \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE &&
                    !$magentoAttributeHelper->isExistInAttributesArray(
                        $repricing->getData('min_price_attribute'), $attributesByInputTypes['text_price']
                    ) && $repricing->getData('min_price_attribute') != '') {

                    $attrs = [
                        'attribute_code' => $repricing->getData('min_price_attribute'),
                        'selected' => 'selected'
                    ];

                    $preparedAttributes[] = [
                        'attrs' => $attrs,
                        'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE,
                        'label' => $magentoAttributeHelper->getAttributeLabel(
                            $repricing->getData('min_price_attribute')
                        ),
                    ];
                }

                foreach ($attributesByInputTypes['text_price'] as $attribute) {
                    $attrs = ['attribute_code' => $attribute['code']];
                    if (
                        $repricing->getMinPriceMode() == $priceModeAttribute
                        && $attribute['code'] == $repricing->getData('min_price_attribute')
                    ) {
                        $attrs['selected'] = 'selected';
                    }
                    $preparedAttributes[] = [
                        'attrs' => $attrs,
                        'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE,
                        'label' => $attribute['label'],
                    ];
                }

                $priceCoefficient = $this->elementFactory->create('text', ['data' => [
                    'html_id' => 'min_price_coefficient',
                    'name' => 'repricing[min_price_coefficient]',
                    'label' => '',
                    'value' => $repricing->getData('min_price_coefficient'),
                    'class' => 'M2ePro-validate-price-coefficient',
                ]]);
                $priceCoefficient->setForm($form);

                $fieldTooltip = '<div class="fix-magento-tooltip" style="margin-left: 20px; margin-right: 20px;">' .
                    $this->getTooltipHtml(
                        $this->__('
    Min Price value is the lowest Price which you could sell your Item for.<br/>
    You can find more detailed information about it
    <a href="%url1%" target="_blank" class="external-link">here</a>.<br/><br/>

    You can select the configurations for automatic updating of the Min Price value by selecting from these options:
    <br/>
    <strong>Manually</strong> - means that the according value will be manually provided for the Products;<br/>
    <strong>Less than Regular Price by Value</strong> - means that the Min Price value will be taken as a Regular Price
    decreased by the value you set.<br/>
    For example, you set the Value 5. Your Regular price is 17. So the Min Price will be 12.<br/>
    <strong>Less than Regular Price by Percent</strong> - means that the Min Price value will be taken as a
    Regular Price decreased by the percent you set.<br/>
    For example, you set 50 Percent. Your regular price is 10. So the Min Price will be 5.<br/>
    <strong>From Magento Attribute</strong> - means that the Min Price value will be taken from the selected Attribute.
    <br/><br/>
    Please note, only common (available in all Attribute sets in your Magento), Text or Price field Attributes are
    available for selection.<br/><br/>

    More detailed information on how to work with this option you can find
    <a href="%url2%" target="_blank" class="external-link">here</a>.',
                            $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/igAkAQ'),
                            $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/JQMkAQ')
                        )
                    ) .
                    '</div>';

                $value = (
                    $repricing->getMinPriceMode() ==
                    \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE
                ) ? '' : $repricing->getMinPriceMode();

                $fieldset->addField(
                    'min_price_mode',
                    self::SELECT,
                    [
                        'name' => 'repricing[min_price_mode]',
                        'label' => $this->__('Update Min Price'),
                        'values' => [
                            \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_MANUAL => $this->__('Manually'),
                            [
                                'label' => $this->__('Less than Regular Price by Value'),
                                'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::MIN_PRICE_MODE_REGULAR_VALUE,
                                'attrs' => [
                                    'class' => 'repricing-min-price-mode-regular-depended'
                                ]
                            ],
                            [
                                'label' => $this->__('Less than Regular Price by Percent'),
                                'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::MIN_PRICE_MODE_REGULAR_PERCENT,
                                'attrs' => [
                                    'class' => 'repricing-min-price-mode-regular-depended'
                                ]
                            ],
                            [
                                'label' => $this->__('Magento Attributes'),
                                'value' => $preparedAttributes,
                                'attrs' => [
                                    'is_magento_attribute' => true
                                ]
                            ]
                        ],
                        'create_magento_attribute' => true,
                        'value' => $value,
                        'css_class' => 'price_mode',
                        'after_element_html' => $fieldTooltip . '<span id="min_price_coefficient_td">'
                            . $priceCoefficient->toHtml() . $tooltipPriceCoefficient .
                        '</span>'
                    ]
                )->addCustomAttribute('allowed_attribute_types', 'text,price');

                $fieldset->addField('min_price_value',
                    'text',
                    [
                        'container_id' => 'min_price_value_tr',
                        'label' => $this->__('Min Price Value'),
                        'name' => 'repricing[min_price_value]',
                        'value' => $repricing->getData('min_price_value'),
                        'class' => 'M2ePro-required-when-visible M2ePro-account-repricing-price-value',
                        'required' => true,
                        'tooltip' => $this->__(
                            'Provide the Value which you would like to decrease the Regular Price by.'
                        ),
                        'field_extra_attributes' => 'style="display: none;"'
                    ]
                );

                $fieldset->addField('min_price_percent',
                    'text',
                    [
                        'container_id' => 'min_price_percent_tr',
                        'label' => $this->__('Min Price Percent'),
                        'name' => 'repricing[min_price_percent]',
                        'value' => $repricing->getData('min_price_percent'),
                        'class' => 'M2ePro-required-when-visible M2ePro-account-repricing-price-value',
                        'required' => true,
                        'tooltip' => $this->__(
                            'Provide the Percent Value which you would like to decrease the Regular Price by.'
                        ),
                        'field_extra_attributes' => 'style="display: none;"'
                    ]
                );

                $fieldset->addField('min_price_variation_mode',
                    self::SELECT,
                    [
                        'container_id' => 'min_price_variation_mode_tr',
                        'label' => $this->__('Min Variation Price Source'),
                        'class' => 'select-main',
                        'name' => 'repricing[min_price_variation_mode]',
                        'values' => [
                            \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_VARIATION_MODE_PARENT =>
                                $this->__('Main Product'),
                            \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_VARIATION_MODE_CHILDREN =>
                                $this->__('Associated Products')
                        ],
                        'value' => $repricing->getMinPriceVariationMode(),
                        'tooltip' => $this->__(
                            'Determines where the Price for Bundle Products Options should be taken from.'
                        ),
                        'field_extra_attributes' => 'style="display: none;"'
                    ]
                );

                $fieldset->addField('min_price_warning',
                    self::CUSTOM_CONTAINER,
                    [
                        'container_id' => 'min_price_warning_tr',
                        'css_class' => 'm2epro-custom-container-full-width',
                        'style' => '',
                        'text' => <<<HTML
<div class="messages m2epro-messages">
    <div class="message message-error error">
        <div data-ui-id="messages-message-error">
            {$this->__('Min Price value is required to be specified to guarantee that M2E
                Amazon Repricing Service will never set the Price of your Offer
                lower than Min allowed Price. It allows Sellers to automatically
                prevent any incorrect Price values to be set for their Items.<br/><br/>
                The dynamic updating of the Min Price value cannot give the 100%
                assurance that all the data will be properly set and the correct
                Price will be used for the Item. Thus, more preferable and reliable
                option is Manual updating of the Min Price value.')}
        </div>
    </div>
</div>
HTML
                        ,
                        'field_extra_attributes' => 'style="display: none;"'
                    ]
                );

                // Max Price
                // --------------------------

                $fieldset->addField(
                    'max_price_attribute',
                    'hidden',
                    [
                        'name' => 'repricing[max_price_attribute]',
                        'value' => $repricing->getData('max_price_attribute')
                    ]
                );

                $preparedAttributes = [];

                if ($repricing->getMaxPriceMode() == \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE &&
                    !$magentoAttributeHelper->isExistInAttributesArray(
                        $repricing->getData('max_price_attribute'), $attributesByInputTypes['text_price']
                    ) && $repricing->getData('max_price_attribute') != '') {

                    $attrs = [
                        'attribute_code' => $repricing->getData('max_price_attribute'),
                        'selected' => 'selected'
                    ];

                    $preparedAttributes[] = [
                        'attrs' => $attrs,
                        'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE,
                        'label' => $magentoAttributeHelper->getAttributeLabel(
                            $repricing->getData('max_price_attribute')
                        ),
                    ];
                }

                foreach ($attributesByInputTypes['text_price'] as $attribute) {
                    $attrs = ['attribute_code' => $attribute['code']];
                    if (
                        $repricing->getMaxPriceMode() == $priceModeAttribute
                        && $attribute['code'] == $repricing->getData('max_price_attribute')
                    ) {
                        $attrs['selected'] = 'selected';
                    }
                    $preparedAttributes[] = [
                        'attrs' => $attrs,
                        'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE,
                        'label' => $attribute['label'],
                    ];
                }

                $fieldTooltip = '<div class="fix-magento-tooltip" style="margin-left: 20px; margin-right: 20px;">' .
                    $this->getTooltipHtml(
                        $this->__('
    Max Price value is the highest Price which you could sell your Item for.<br/>
    You can find more detailed information about it
    <a href="%url1%" target="_blank" class="external-link">here</a>.<br/><br/>

    <strong>Manually</strong> - means that the according value will be manually provided for the Products;<br/>
    <strong>More than Regular Price by Value</strong> - means that the Max Price value will be taken as a Regular
    Price increased by the value you set.<br/>
    For example, you set the Value 5. Your Regular price is 17. So the Max Price will be 22.<br/>
    <strong>More than Regular Price by Percent</strong> - means that the Max Price value will be taken as a Regular
    Price increased by the percent you set.<br/>
    For example, you set 50 Percent. Your regular price is 10. So the Max Price will be 15.<br/>
    <strong>From Magento Attribute</strong> - means that the Max Price value will be taken from the selected Attribute.
    <br/><br/>

    Please note, only common (available in all Attribute sets in your Magento), Text or Price field Attributes are
    available for selection.<br/><br/>

    More detailed information on how to work with this option you can find
    <a href="%url2%" target="_blank" class="external-link">here</a>.',
                            $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/igAkAQ'),
                            $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/JQMkAQ')
                        )
                    ) .
                '</div>';

                $priceCoefficient = $this->elementFactory->create('text', ['data' => [
                    'html_id' => 'max_price_coefficient',
                    'name' => 'repricing[max_price_coefficient]',
                    'label' => '',
                    'value' => $repricing->getData('max_price_coefficient'),
                    'class' => 'M2ePro-validate-price-coefficient',
                ]]);
                $priceCoefficient->setForm($form);

                $value = (
                    $repricing->getMaxPriceMode() ==
                    \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE
                ) ? '' : $repricing->getMaxPriceMode();

                $fieldset->addField(
                    'max_price_mode',
                    self::SELECT,
                    [
                        'name' => 'repricing[max_price_mode]',
                        'label' => $this->__('Update Max Price'),
                        'values' => [
                            \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_MANUAL => $this->__('Manually'),
                            [
                                'label' => $this->__('More than Regular Price by Value'),
                                'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::MAX_PRICE_MODE_REGULAR_VALUE,
                                'attrs' => [
                                    'class' => 'repricing-max-price-mode-regular-depended'
                                ]
                            ],
                            [
                                'label' => $this->__('More than Regular Price by Percent'),
                                'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::MAX_PRICE_MODE_REGULAR_PERCENT,
                                'attrs' => [
                                    'class' => 'repricing-max-price-mode-regular-depended'
                                ]
                            ],
                            [
                                'label' => $this->__('Magento Attributes'),
                                'value' => $preparedAttributes,
                                'attrs' => [
                                    'is_magento_attribute' => true
                                ]
                            ]
                        ],
                        'create_magento_attribute' => true,
                        'value' => $value,
                        'css_class' => 'price_mode',
                        'after_element_html' => $fieldTooltip . '<span id="max_price_coefficient_td">'
                            . $priceCoefficient->toHtml() . $tooltipPriceCoefficient .
                        '</span>'
                    ]
                )->addCustomAttribute('allowed_attribute_types', 'text,price');

                $fieldset->addField('max_price_value',
                    'text',
                    [
                        'container_id' => 'max_price_value_tr',
                        'label' => $this->__('Max Price Value'),
                        'name' => 'repricing[max_price_value]',
                        'value' => $repricing->getData('max_price_value'),
                        'class' => 'M2ePro-required-when-visible M2ePro-account-repricing-price-value',
                        'required' => true,
                        'tooltip' => $this->__(
                            'Provide the Value which you would like to decrease the Regular Price by.'
                        ),
                        'field_extra_attributes' => 'style="display: none;"'
                    ]
                );

                $fieldset->addField('max_price_percent',
                    'text',
                    [
                        'container_id' => 'max_price_percent_tr',
                        'label' => $this->__('Max Price Percent'),
                        'name' => 'repricing[max_price_percent]',
                        'value' => $repricing->getData('max_price_percent'),
                        'class' => 'M2ePro-required-when-visible M2ePro-account-repricing-price-value',
                        'required' => true,
                        'tooltip' => $this->__(
                            'Provide the Percent Value which you would like to decrease the Regular Price by.'
                        ),
                        'field_extra_attributes' => 'style="display: none;"'
                    ]
                );

                $fieldset->addField('max_price_variation_mode',
                    self::SELECT,
                    [
                        'container_id' => 'max_price_variation_mode_tr',
                        'label' => $this->__('Max Variation Price Source'),
                        'class' => 'select-main',
                        'name' => 'repricing[max_price_variation_mode]',
                        'values' => [
                            \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_VARIATION_MODE_PARENT =>
                                $this->__('Main Product'),
                            \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_VARIATION_MODE_CHILDREN =>
                                $this->__('Associated Products')
                        ],
                        'value' => $repricing->getMaxPriceVariationMode(),
                        'tooltip' => $this->__(
                            'Determaxes where the Price for Bundle Products Options should be taken from.'
                        ),
                        'field_extra_attributes' => 'style="display: none;"'
                    ]
                );

                $fieldset->addField('max_price_warning',
                    self::CUSTOM_CONTAINER,
                    [
                        'container_id' => 'max_price_warning_tr',
                        'style' => '',
                        'text' => <<<HTML
<div class="messages">
    <div class="message message-error error">
        <div data-ui-id="messages-message-error">
            {$this->__('Max Price value is required to be specified to guarantee that M2E
                Amazon Repricing Service will never set the Price of your Offer
                higher than Max allowed Price. It allows Sellers to automatically
                prevent any incorrect Price values to be set for their Items.<br/><br/>
                The dynamic updating of the Max Price value cannot give the 100%
                assurance that all the data will be properly set and the correct
                Price will be used for the Item. Thus, more preferable and reliable
                option is Manual updating of the Max Price value.')}
        </div>
    </div>
</div>
HTML
                        ,
                        'field_extra_attributes' => 'style="display: none;"'
                    ]
                );

                // Disable Repricing
                // ----------------------

                $fieldset->addField(
                    'disable_mode_attribute',
                    'hidden',
                    [
                        'name' => 'repricing[disable_mode_attribute]',
                        'value' => $repricing->getData('disable_mode_attribute')
                    ]
                );

                $preparedAttributes = [];

                $priceModeAttribute = \Ess\M2ePro\Model\Amazon\Account\Repricing::DISABLE_MODE_ATTRIBUTE;

                if ($repricing->getDisableMode() == $priceModeAttribute &&
                    !$magentoAttributeHelper->isExistInAttributesArray(
                        $repricing->getData('disable_mode_attribute'), $attributesByInputTypes['boolean']
                    ) && $repricing->getData('disable_mode_attribute') != '') {

                    $attrs = [
                        'attribute_code' => $repricing->getData('disable_mode_attribute'),
                        'selected' => 'selected'
                    ];

                    $preparedAttributes[] = [
                        'attrs' => $attrs,
                        'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::DISABLE_MODE_ATTRIBUTE,
                        'label' => $magentoAttributeHelper->getAttributeLabel(
                            $repricing->getData('disable_mode_attribute')
                        ),
                    ];
                }

                foreach ($attributesByInputTypes['boolean'] as $attribute) {
                    $attrs = ['attribute_code' => $attribute['code']];
                    if (
                        $repricing->getDisableMode() == $priceModeAttribute
                        && $attribute['code'] == $repricing->getData('disable_mode_attribute')
                    ) {
                        $attrs['selected'] = 'selected';
                    }
                    $preparedAttributes[] = [
                        'attrs' => $attrs,
                        'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::DISABLE_MODE_ATTRIBUTE,
                        'label' => $attribute['label'],
                    ];
                }

                $value = (
                    $repricing->getDisableMode() ==
                    \Ess\M2ePro\Model\Amazon\Account\Repricing::DISABLE_MODE_ATTRIBUTE
                ) ? '' : $repricing->getDisableMode();

                $fieldset->addField(
                    'disable_mode',
                    self::SELECT,
                    [
                        'name' => 'repricing[disable_mode]',
                        'label' => $this->__('Disable Repricing'),
                        'values' => [
                            \Ess\M2ePro\Model\Amazon\Account\Repricing::DISABLE_MODE_MANUAL => $this->__('Manually'),
                            \Ess\M2ePro\Model\Amazon\Account\Repricing::DISABLE_MODE_PRODUCT_STATUS =>
                                $this->__('When Status is Disabled'),
                            [
                                'label' => $this->__('Magento Attributes'),
                                'value' => $preparedAttributes,
                                'attrs' => [
                                    'is_magento_attribute' => true
                                ]
                            ]
                        ],
                        'create_magento_attribute' => true,
                        'value' => $value,
                        'tooltip' => $this->__('
    This feature allows you to set when you would like to disable dynamic price repricing for your items.<br/>
    You can find more detailed information about it
    <a href="%url1%" target="_blank" class="external-link">here</a>.<br/><br/>

    <strong>Manually</strong> - means that the dynamic repricing can be disabled only manually;<br/>
    <strong>When Status is Disabled</strong> - means that the dynamic repricing will be automatically stopped for
    the Product which has Disabled status in Magento;<br/>
    <strong>From Magento Attribute</strong> - means that the dynamic repricing will be automatically stopped for
    the product if Yes value is provided in the selected Attribute.<br/><br/>
    Please note, only common (available in all Attribute sets in your Magento), Text or Price field Attributes are
    available for selection.<br/><br/>

    More detailed information on how to work with this option you can find
    <a href="%url2%" target="_blank" class="external-link">here</a>.',
                            $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/PAMkAQ'),
                            $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/JQMkAQ')
                        )
                    ]
                )->addCustomAttribute('allowed_attribute_types', 'boolean');

                $this->js->addOnReadyJs(<<<JS
    require([
        'M2ePro/Amazon/Account',
    ], function(){
        $('regular_price_mode')
            .observe('change', AmazonAccountObj.regular_price_mode_change)
            .simulate('change');

        $('min_price_mode')
            .observe('change', AmazonAccountObj.min_price_mode_change)
            .simulate('change');

        $('max_price_mode')
            .observe('change', AmazonAccountObj.max_price_mode_change)
            .simulate('change');

        $('disable_mode')
            .observe('change', AmazonAccountObj.disable_mode_change)
            .simulate('change');
    });
JS
                );

            }

        } else {

            $fieldset->addField('link_repricing',
                'note',
                [
                    'text' => <<<HTML
    <span>{$this->__('First, you have to link this Amazon Account with Amazon Repricing Tool')}</span>&nbsp;
    <button type="button" class="action primary" onclick="AmazonAccountObj.linkOrRegisterRepricing()">
        {$this->__('Link Now')}
    </button>
HTML
                    ,
                    'style' => 'text-align: center;'
                ]
            );
        }

        $this->css->add(
<<<CSS
    .field-link_repricing .control {
        margin-left: 17% !important;
        width: 78% !important;
    }

    .field-unlink_repricing .control {
        margin-left: 17% !important;
        width: 78% !important;
    }

    #additional_settings label.addafter input[type="text"].input-text {
        width: 15% !important;
    }

    #additional_settings .price_mode label.addafter {
        display: initial !important;
    }

    #min_price_warning_tr .control {
        width: 100% !important;
        margin-left: 0 !important;
    }
    #max_price_warning_tr .control {
        width: 100% !important;
        margin-left: 0 !important;
    }
CSS
        );

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Amazon\Account\Repricing')
        );

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Amazon\Account\Repricing', [
            'id' => $account->getId()
        ]));

        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function getRepricingProductsCount()
    {
        $account = $this->getHelper('Data\GlobalData')->getValue('edit_account');

        $listingProductObject = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK, 'Listing\Product'
        );

        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product\Collection $collection */
        $collection = $listingProductObject->getCollection();

        $collection->getSelect()->join(
            array('l' => $this->activeRecordFactory->getObject('Listing')->getResource()->getMainTable()),
            '(`l`.`id` = `main_table`.`listing_id`)',
            array()
        );

        $collection->getSelect()->where("`second_table`.`is_variation_parent` = 0");
        $collection->getSelect()->where("`l`.`account_id` = ?", $account->getId());

        $collection->getSelect()->join(
            [
                'malpr' => $this->activeRecordFactory->getObject('Amazon\Listing\Product\Repricing')
                    ->getResource()->getMainTable()
            ],
            '(`second_table`.`listing_product_id` = `malpr`.`listing_product_id`)',
            []
        );

        return $collection->count();
    }
}