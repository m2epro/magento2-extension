<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Repricer\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Magento\Framework\Message\MessageInterface;

class Form extends AbstractForm
{
    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    protected $magentoAttributeHelper;
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /**
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->magentoAttributeHelper = $magentoAttributeHelper;
        $this->supportHelper = $supportHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->dataHelper = $dataHelper;

        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return \Ess\M2ePro\Block\Adminhtml\Amazon\Repricer\Edit\Form
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \ReflectionException
     */
    protected function _prepareForm(): Form
    {
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl('*/*/save'),
                    'method' => 'post',
                    'enctype' => 'multipart/form-data',
                ],
            ]
        );
        $form->setUseContainer(true);
        $this->setForm($form);

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->globalDataHelper->getValue('edit_account');

        $isRepricingLinked = $account->getChildObject()->isRepricing();

        $repricing = null;
        if ($isRepricingLinked) {
            $repricing = $account->getChildObject()->getRepricing();
        }

        $allAttributes = $this->magentoAttributeHelper->getAll();
        $attributesByInputTypes = [
            'text_price' => $this->magentoAttributeHelper->filterByInputTypes($allAttributes, ['text', 'price']),
            'boolean' => $this->magentoAttributeHelper->filterByInputTypes($allAttributes, ['boolean']),
        ];

        $form->addField(
            'amazon_accounts_repricing',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    <<<HTML
<p>If necessary, you can manage additional settings of your Amazon items managed by Repricer.</p>
<p>Head over to <a href="%url%" target="_blank" class="external-link">docs</a> for detailed information.</p>
HTML
                    ,
                    $this->supportHelper->getDocumentationArticleUrl('x/CP4kB')
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'additional_settings',
            [
                'legend' => '',
                'collapsable' => false,
            ]
        );

        // Regular price
        // --------------------------

        $preparedAttributes = [];
        $priceCoefficient = '';
        $value = '';
        $tooltipPriceCoefficient = '';

        if ($repricing) {
            $tooltipPriceCoefficient = '<div class="fix-magento-tooltip" style="margin-left: 20px;">' .
                $this->getTooltipHtml(
                    __('Absolute figure (+8,-3), percentage (+15%, -20%) or Currency rate (1.44)')
                ) .
                '</div>';

            $fieldset->addField(
                'regular_price_attribute',
                'hidden',
                [
                    'name' => 'repricing[regular_price_attribute]',
                    'value' => $repricing->getData('regular_price_attribute'),
                ]
            );

            $priceModeAttribute = \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE;

            if (
                $repricing->getRegularPriceMode() == $priceModeAttribute &&
                !$this->magentoAttributeHelper->isExistInAttributesArray(
                    $repricing->getData('regular_price_attribute'),
                    $attributesByInputTypes['text_price']
                ) && $repricing->getData('regular_price_attribute') != ''
            ) {
                $attrs = [
                    'attribute_code' => $repricing->getData('regular_price_attribute'),
                    'selected' => 'selected',
                ];

                $preparedAttributes[] = [
                    'attrs' => $attrs,
                    'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE,
                    'label' => $this->magentoAttributeHelper->getAttributeLabel(
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

            $priceCoefficient = $this->elementFactory->create('text', [
                'data' => [
                    'html_id' => 'regular_price_coefficient',
                    'name' => 'repricing[regular_price_coefficient]',
                    'label' => '',
                    'value' => $repricing->getData('regular_price_coefficient'),
                    'class' => 'M2ePro-validate-price-coefficient',
                ],
            ])->setForm($form)->toHtml();

            $value = (
                $repricing->getRegularPriceMode() ==
                \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE
            ) ? '' : $repricing->getRegularPriceMode();
        }

        $fieldTooltip = '<div class="fix-magento-tooltip" style="margin-left: 20px; margin-right: 20px;">' .
            $this->getTooltipHtml(
                __(
                    '
    <strong>Regular Price</strong> is a common Price without any changes.
    This value is used for Repricing Rules configuration and plays the role of the roll-back Price in case
    the Rules cannot be applied or the Goals provided in the Repricing Rules cannot be achieved.
    <a href="%url1%" target="_blank">Learn more</a> about using the Regular Price in Repricing Service.
    <br /><br />
    Specify the settings for automatic update of the Regular Price:
    <br /><br />
    <strong>Manually</strong> - means that the Product Price value will be provided manually;<br />
    <strong>According to Selling Policy settings</strong> - means that the Price value will be set based
    on the Selling Policy settings;<br />
    <strong>From Product Price</strong> - means that the Price value
    will be taken from Magento Product Price Attribute;<br />
    <strong>From Special Price</strong> - means that the Price value
    will be taken from Magento Special Price Attribute;<br />
    <strong>From Magento Attribute</strong> - means that the Price value
    will be taken from the selected Magento Attribute.<br />
    <br />
    Please note, only common (available in all Attribute sets in your Magento) Text or Price field Attributes
    are available for the selection.
    <br /><br />
    More detailed information on how to work with this option can be found
    <a href="%url2%" target="_blank" class="external-link">here</a>.',
                    $this->supportHelper->getDocumentationArticleUrl('x/AAAZD'),
                    $this->supportHelper->getDocumentationArticleUrl('x/CP4kB')
                )
            ) .
            '</div>';

        $fieldset->addField(
            'regular_price_mode',
            self::SELECT,
            [
                'name' => 'repricing[regular_price_mode]',
                'label' => __('Update Regular Price'),
                'values' => [
                    \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_MANUAL => __('Manually'),
                    \Ess\M2ePro\Model\Amazon\Account\Repricing::REGULAR_PRICE_MODE_PRODUCT_POLICY =>
                        __('According to Selling Policy'),
                    \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_PRODUCT =>
                        __('From Product Price'),
                    \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_SPECIAL =>
                        __('From Special Price'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'create_magento_attribute' => true,
                'value' => $value,
                'css_class' => 'price_mode',
                'after_element_html' => $fieldTooltip . '<span id="regular_price_coefficient_td">' .
                    $priceCoefficient . $tooltipPriceCoefficient .
                    '</span>',
                'style' => 'max-width: 310px',
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');

        $fieldset->addField(
            'regular_price_variation_mode',
            self::SELECT,
            [
                'container_id' => 'regular_price_variation_mode_tr',
                'label' => __('Regular Variation Price Source'),
                'class' => 'select-main',
                'name' => 'repricing[regular_price_variation_mode]',
                'values' => [
                    \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_VARIATION_MODE_PARENT =>
                        __('Main Product'),
                    \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_VARIATION_MODE_CHILDREN =>
                        __('Associated Products'),
                ],
                'value' => ($repricing) ? $repricing->getRegularPriceVariationMode() : '',
                'tooltip' => __('Choose the source of the price value for Bundle Products variations.'),
                'field_extra_attributes' => 'style="display: none;"',
            ]
        );

        // Min Price
        // --------------------------

        $preparedAttributes = [];
        $priceCoefficient = '';
        $value = '';

        if ($repricing) {
            $fieldset->addField(
                'min_price_attribute',
                'hidden',
                [
                    'name' => 'repricing[min_price_attribute]',
                    'value' => $repricing->getData('min_price_attribute'),
                ]
            );

            if (
                $repricing->getMinPriceMode() == \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE &&
                !$this->magentoAttributeHelper->isExistInAttributesArray(
                    $repricing->getData('min_price_attribute'),
                    $attributesByInputTypes['text_price']
                ) && $repricing->getData('min_price_attribute') != ''
            ) {
                $attrs = [
                    'attribute_code' => $repricing->getData('min_price_attribute'),
                    'selected' => 'selected',
                ];

                $preparedAttributes[] = [
                    'attrs' => $attrs,
                    'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE,
                    'label' => $this->magentoAttributeHelper->getAttributeLabel(
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

            $priceCoefficient = $this->elementFactory->create('text', [
                'data' => [
                    'html_id' => 'min_price_coefficient',
                    'name' => 'repricing[min_price_coefficient]',
                    'label' => '',
                    'value' => $repricing->getData('min_price_coefficient'),
                    'class' => 'M2ePro-validate-price-coefficient',
                ],
            ])->setForm($form)->toHtml();

            $value = (
                $repricing->getMinPriceMode() ==
                \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE
            ) ? '' : $repricing->getMinPriceMode();
        }

        $fieldTooltip = '<div class="fix-magento-tooltip" style="margin-left: 20px; margin-right: 20px;">' .
            $this->getTooltipHtml(
                __(
                    '
<strong>Min Price</strong> is the lowest Price which you could sell your Item at.
<a href="%url1%" target="_blank">Learn more</a> about using the Max Price in Repricing Service.
<br /><br />
Specify the settings for automatic update of the Min Price:
<br /><br />
<strong>Manually</strong> - means that the Product Price value will be provided manually;<br />
<strong>Less than Regular Price by Value</strong> - means that the Min Price value
will be taken as a Regular Price decreased by the value you set.
For example, you set the Value 5. Your Regular price is 17. So the Min Price will be 12.<br />
<strong>Less than Regular Price by Percent</strong> - means that the Min Price value
will be taken as a Regular Price decreased by the percent you set.
For example, you set 50%. Your regular price is 10. So the Min Price will be 5.<br />
<strong>From Magento Attribute</strong> - means that the Price value will be taken from the selected Magento Attribute.
<br /><br />
Please note, only common (available in all Attribute sets in your Magento)
Text or Price field Attributes are available for the selection.
<br /><br />
More detailed information on how to work with this option can be found
<a href="%url2%" target="_blank" class="external-link">here</a>.',
                    $this->supportHelper->getDocumentationArticleUrl('x/AAAZD'),
                    $this->supportHelper->getDocumentationArticleUrl('x/CP4kB')
                )
            ) .
            '</div>';

        $fieldset->addField(
            'min_price_mode',
            self::SELECT,
            [
                'name' => 'repricing[min_price_mode]',
                'label' => __('Update Min Price'),
                'values' => [
                    \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_MANUAL => __('Manually'),
                    [
                        'label' => __('Less than Regular Price by Value'),
                        'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::MIN_PRICE_MODE_REGULAR_VALUE,
                        'attrs' => [
                            'class' => 'repricing-min-price-mode-regular-depended',
                        ],
                    ],
                    [
                        'label' => __('Less than Regular Price by Percent'),
                        'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::MIN_PRICE_MODE_REGULAR_PERCENT,
                        'attrs' => [
                            'class' => 'repricing-min-price-mode-regular-depended',
                        ],
                    ],
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'create_magento_attribute' => true,
                'value' => $value,
                'css_class' => 'price_mode',
                'after_element_html' => $fieldTooltip . '<span id="min_price_coefficient_td">'
                    . $priceCoefficient . $tooltipPriceCoefficient .
                    '</span>',
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');

        $fieldset->addField(
            'min_price_value',
            'text',
            [
                'container_id' => 'min_price_value_tr',
                'label' => __('Min Price Value'),
                'name' => 'repricing[min_price_value]',
                'value' => ($repricing) ? $repricing->getData('min_price_value') : '',
                'class' => 'M2ePro-required-when-visible M2ePro-account-repricing-price-value',
                'required' => true,
                'tooltip' => __(
                    'Provide the Value which you would like to decrease the Regular Price by.'
                ),
                'field_extra_attributes' => 'style="display: none;"',
            ]
        );

        $fieldset->addField(
            'min_price_percent',
            'text',
            [
                'container_id' => 'min_price_percent_tr',
                'label' => __('Min Price Percent'),
                'name' => 'repricing[min_price_percent]',
                'value' => ($repricing) ? $repricing->getData('min_price_percent') : '',
                'class' => 'M2ePro-required-when-visible M2ePro-account-repricing-price-percent',
                'required' => true,
                'tooltip' => __(
                    'Provide the Percent Value which you would like to decrease the Regular Price by.'
                ),
                'field_extra_attributes' => 'style="display: none;"',
            ]
        );

        $fieldset->addField(
            'min_price_variation_mode',
            self::SELECT,
            [
                'container_id' => 'min_price_variation_mode_tr',
                'label' => __('Min Variation Price Source'),
                'class' => 'select-main',
                'name' => 'repricing[min_price_variation_mode]',
                'values' => [
                    \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_VARIATION_MODE_PARENT =>
                        __('Main Product'),
                    \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_VARIATION_MODE_CHILDREN =>
                        __('Associated Products'),
                ],
                'value' => ($repricing) ? $repricing->getMinPriceVariationMode() : '',
                'tooltip' => __('Choose the source of the price value for Bundle Products variations.'),
                'field_extra_attributes' => 'style="display: none;"',
            ]
        );

        $fieldset->addField(
            'min_price_warning_tr',
            self::MESSAGES,
            [
                'messages' => [
                    [
                        'type' => MessageInterface::TYPE_WARNING,
                        'content' => __(
                            'Min Price value is required to be specified to guarantee that M2E
                            Amazon Repricing Service will never set the Price of your Offer
                            lower than Min allowed Price. It allows Sellers to automatically
                            prevent any incorrect Price values to be set for their Items.<br/><br/>
                            The dynamic updating of the Min Price value cannot give the 100%
                            assurance that all the data will be properly set and the correct
                            Price will be used for the Item. Thus, more preferable and reliable
                            option is Manual updating of the Min Price value.'
                        ),
                    ],
                ],
                'style' => 'display: none',
            ]
        );

        // Max Price
        // --------------------------

        $preparedAttributes = [];
        $priceCoefficient = '';
        $value = '';

        if ($repricing) {
            $fieldset->addField(
                'max_price_attribute',
                'hidden',
                [
                    'name' => 'repricing[max_price_attribute]',
                    'value' => $repricing->getData('max_price_attribute'),
                ]
            );

            if (
                $repricing->getMaxPriceMode() == \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE &&
                !$this->magentoAttributeHelper->isExistInAttributesArray(
                    $repricing->getData('max_price_attribute'),
                    $attributesByInputTypes['text_price']
                ) && $repricing->getData('max_price_attribute') != ''
            ) {
                $attrs = [
                    'attribute_code' => $repricing->getData('max_price_attribute'),
                    'selected' => 'selected',
                ];

                $preparedAttributes[] = [
                    'attrs' => $attrs,
                    'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE,
                    'label' => $this->magentoAttributeHelper->getAttributeLabel(
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

            $priceCoefficient = $this->elementFactory->create('text', [
                'data' => [
                    'html_id' => 'max_price_coefficient',
                    'name' => 'repricing[max_price_coefficient]',
                    'label' => '',
                    'value' => $repricing->getData('max_price_coefficient'),
                    'class' => 'M2ePro-validate-price-coefficient',
                ],
            ])->setForm($form)->toHtml();

            $value = (
                $repricing->getMaxPriceMode() ==
                \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_ATTRIBUTE
            ) ? '' : $repricing->getMaxPriceMode();
        }

        $fieldTooltip = '<div class="fix-magento-tooltip" style="margin-left: 20px; margin-right: 20px;">' .
            $this->getTooltipHtml(
                $this->__(
                    '
<strong>Max Price</strong> is the highest Price which you could sell your Item at.
<a href="%url%" target="_blank">Learn more</a> about using the Min Price in Repricing Service.
<br /><br />
Specify the settings for automatic update of the Max Price:
<br /><br />
<strong>Manually</strong> - means that the Product Price value will be provided manually;<br />
<strong>More than Regular Price by Value</strong> - means that the Max Price value
will be taken as a Regular Price increased by the value you set.
For example, you set the Value 5. Your Regular price is 17. So the Max Price will be 22.<br />
<strong>More than Regular Price by Percent</strong> - means that the Max Price value
will be taken as a Regular Price increased by the percent you set.
For example, you set 50 Percent. Your regular price is 10. So the Max Price will be 15.<br />
<strong>From Magento Attribute</strong> - means that the Max Price value will be taken from the selected Attribute.
<br /><br />
Please note, only common (available in all Attribute sets in your Magento)
Text or Price field Attributes are available for the selection.
<br /><br />
More detailed information on how to work with this option can be found
<a href="%url2%" target="_blank" class="external-link">here</a>.',
                    $this->supportHelper->getDocumentationArticleUrl('x/AAAZD'),
                    $this->supportHelper->getDocumentationArticleUrl('x/CP4kB')
                )
            ) .
            '</div>';

        $fieldset->addField(
            'max_price_mode',
            self::SELECT,
            [
                'name' => 'repricing[max_price_mode]',
                'label' => __('Update Max Price'),
                'values' => [
                    \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_MODE_MANUAL => __('Manually'),
                    [
                        'label' => __('More than Regular Price by Value'),
                        'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::MAX_PRICE_MODE_REGULAR_VALUE,
                        'attrs' => [
                            'class' => 'repricing-max-price-mode-regular-depended',
                        ],
                    ],
                    [
                        'label' => __('More than Regular Price by Percent'),
                        'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::MAX_PRICE_MODE_REGULAR_PERCENT,
                        'attrs' => [
                            'class' => 'repricing-max-price-mode-regular-depended',
                        ],
                    ],
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'create_magento_attribute' => true,
                'value' => $value,
                'css_class' => 'price_mode',
                'after_element_html' => $fieldTooltip . '<span id="max_price_coefficient_td">'
                    . $priceCoefficient . $tooltipPriceCoefficient .
                    '</span>',
            ]
        )->addCustomAttribute('allowed_attribute_types', 'text,price');

        $fieldset->addField(
            'max_price_value',
            'text',
            [
                'container_id' => 'max_price_value_tr',
                'label' => __('Max Price Value'),
                'name' => 'repricing[max_price_value]',
                'value' => ($repricing) ? $repricing->getData('max_price_value') : '',
                'class' => 'M2ePro-required-when-visible M2ePro-account-repricing-price-value',
                'required' => true,
                'tooltip' => __(
                    'Provide the Value which you would like to decrease the Regular Price by.'
                ),
                'field_extra_attributes' => 'style="display: none;"',
            ]
        );

        $fieldset->addField(
            'max_price_percent',
            'text',
            [
                'container_id' => 'max_price_percent_tr',
                'label' => __('Max Price Percent'),
                'name' => 'repricing[max_price_percent]',
                'value' => ($repricing) ? $repricing->getData('max_price_percent') : '',
                'class' => 'M2ePro-required-when-visible M2ePro-account-repricing-price-percent',
                'required' => true,
                'tooltip' => __(
                    'Provide the Percent Value which you would like to decrease the Regular Price by.'
                ),
                'field_extra_attributes' => 'style="display: none;"',
            ]
        );

        $fieldset->addField(
            'max_price_variation_mode',
            self::SELECT,
            [
                'container_id' => 'max_price_variation_mode_tr',
                'label' => __('Max Variation Price Source'),
                'class' => 'select-main',
                'name' => 'repricing[max_price_variation_mode]',
                'values' => [
                    \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_VARIATION_MODE_PARENT =>
                        __('Main Product'),
                    \Ess\M2ePro\Model\Amazon\Account\Repricing::PRICE_VARIATION_MODE_CHILDREN =>
                        __('Associated Products'),
                ],
                'value' => ($repricing) ? $repricing->getMaxPriceVariationMode() : '',
                'tooltip' => __('Choose the source of the price value for Bundle Products variations.'),
                'field_extra_attributes' => 'style="display: none;"',
            ]
        );

        $fieldset->addField(
            'max_price_warning_tr',
            self::MESSAGES,
            [
                'messages' => [
                    [
                        'type' => MessageInterface::TYPE_WARNING,
                        'content' => __(
                            'Max Price value is required to be specified to guarantee that M2E
                            Amazon Repricing Service will never set the Price of your Offer
                            higher than Max allowed Price. It allows Sellers to automatically
                            prevent any incorrect Price values to be set for their Items.<br/><br/>
                            The dynamic updating of the Max Price value cannot give the 100%
                            assurance that all the data will be properly set and the correct
                            Price will be used for the Item. Thus, more preferable and reliable
                            option is Manual updating of the Max Price value.'
                        ),
                    ],
                ],
                'style' => 'display: none;',
            ]
        );

        // Disable Repricing
        // ----------------------

        $preparedAttributes = [];
        $value = '';

        if ($repricing) {
            $fieldset->addField(
                'disable_mode_attribute',
                'hidden',
                [
                    'name' => 'repricing[disable_mode_attribute]',
                    'value' => $repricing->getData('disable_mode_attribute'),
                ]
            );

            $priceModeAttribute = \Ess\M2ePro\Model\Amazon\Account\Repricing::DISABLE_MODE_ATTRIBUTE;

            if (
                $repricing->getDisableMode() == $priceModeAttribute &&
                !$this->magentoAttributeHelper->isExistInAttributesArray(
                    $repricing->getData('disable_mode_attribute'),
                    $attributesByInputTypes['boolean']
                ) && $repricing->getData('disable_mode_attribute') != ''
            ) {
                $attrs = [
                    'attribute_code' => $repricing->getData('disable_mode_attribute'),
                    'selected' => 'selected',
                ];

                $preparedAttributes[] = [
                    'attrs' => $attrs,
                    'value' => \Ess\M2ePro\Model\Amazon\Account\Repricing::DISABLE_MODE_ATTRIBUTE,
                    'label' => $this->magentoAttributeHelper->getAttributeLabel(
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
        }

        $fieldset->addField(
            'disable_mode',
            self::SELECT,
            [
                'name' => 'repricing[disable_mode]',
                'label' => __('Disable Repricing'),
                'values' => [
                    \Ess\M2ePro\Model\Amazon\Account\Repricing::DISABLE_MODE_MANUAL => __('Manually'),
                    \Ess\M2ePro\Model\Amazon\Account\Repricing::DISABLE_MODE_PRODUCT_STATUS =>
                        __('When Status is Disabled'),
                    [
                        'label' => __('Magento Attributes'),
                        'value' => $preparedAttributes,
                        'attrs' => [
                            'is_magento_attribute' => true,
                        ],
                    ],
                ],
                'create_magento_attribute' => true,
                'value' => $value,
                'after_element_html' =>
                    '<div class="fix-magento-tooltip" style="margin-left: 20px; margin-right: 20px;">' .
                    $this->getTooltipHtml(
                        __(
                            '
This option allows you to set when you would like to disable dynamic repricing for your M2E Pro Items.
<a href="%url1%" target="_blank">Learn more</a> about the Product Disabling feature.
<br /><br />
<strong>Manually</strong> - means that the dynamic repricing can be disabled only manually;<br />
<strong>When Status is Disabled</strong> - means that the dynamic repricing
will be automatically paused for the Product which has Disabled status in Magento;<br />
<strong>From Magento Attribute</strong> - means that the dynamic repricing
will be automatically paused for the Product if ‘Yes’ value is provided in specified Attribute.
<br /><br />
Please note, only common (available in all Attribute sets in your Magento)
Text or Price field Attributes are available for the selection.
<br /><br />
More detailed information on how to work with this option can be found
<a href="%url2%" target="_blank" class="external-link">here</a>.',
                            $this->supportHelper->getDocumentationArticleUrl('x/AAAZD'),
                            $this->supportHelper->getDocumentationArticleUrl('x/CP4kB')
                        )
                    ) . '</div>',
            ]
        )->addCustomAttribute('allowed_attribute_types', 'boolean');

        $this->js->addOnReadyJs(
            <<<JS
            require([
                'M2ePro/Amazon/Repricer',
            ], function(){
                window.AmazonRepricerObj = new AmazonRepricer();
                AmazonRepricerObj.initObservers();
            });
JS
        );

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
CSS
        );

        $this->jsTranslator->add('Please enter correct value.', __('Please enter correct value.'));

        $this->jsPhp->addConstants($this->dataHelper->getClassConstants(\Ess\M2ePro\Helper\Component\Amazon::class));

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Amazon\Account\Repricing::class)
        );

        $this->jsUrl->addUrls(
            $this->dataHelper->getControllerActions('Amazon_Repricer', [
                'id' => $account->getId(),
            ])
        );

        $this->jsUrl->addUrls([
            'formSubmit' => $this->getUrl(
                '*/amazon_repricer_settings/save',
                ['_current' => true, 'id' => $this->getRequest()->getParam('id')]
            ),
        ]);

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
