<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Synchronization\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Amazon\Template\Synchronization;
use Ess\M2ePro\Model\Template\Synchronization as TemplateSynchronization;

class RelistRules extends AbstractForm
{
    private \Ess\M2ePro\Helper\Module\Support $supportHelper;
    private \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper;
    private \Ess\M2ePro\Helper\Data $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        $this->globalDataHelper = $globalDataHelper;
        $this->dataHelper = $dataHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $template = $this->globalDataHelper->getValue('tmp_template');
        $formData = $template !== null
            ? array_merge($template->getData(), $template->getChildObject()->getData()) : [];

        /** @var \Ess\M2ePro\Model\Amazon\Template\Synchronization\Builder $synchronizationBuilder */
        $synchronizationBuilder = $this->modelFactory->getObject('Amazon_Template_Synchronization_Builder');
        $formData = array_merge($synchronizationBuilder->getDefaultData(), $formData);

        $form = $this->_formFactory->create();

        $form->addField(
            'amazon_template_synchronization_relist',
            self::HELP_BLOCK,
            [
                'content' => __(
                    '<p>If <strong>Relist Action</strong> is enabled and the specified conditions are met, ' .
                    'M2E Pro will relist Items that have been inactive on Amazon. ' .
                    '(Relist Action will not list Items that have not been Listed yet)</p><br>' .
                    '<p>If the Item does not get automatically Relisted (usually because of the errors returned ' .
                    'by Amazon), M2E Pro will attempt to relist the Item again only if there is a change of ' .
                    'Product Status, Stock Availability or Quantity in Magento.</p><br>' .
                    '<p>More detailed information about how to work with this Page you can find ' .
                    '<a href="%url" target="_blank" class="external-link">here</a>.</p>',
                    [
                        'url' => $this->supportHelper->getDocumentationArticleUrl('docs/relist-rules'),
                    ]
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_synchronization_relist_filters',
            [
                'legend' => __('General'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'relist_mode',
            self::SELECT,
            [
                'name' => 'relist_mode',
                'label' => __('Relist Action'),
                'value' => $formData['relist_mode'],
                'values' => [
                    0 => __('Disabled'),
                    1 => __('Enabled'),
                ],
                'tooltip' => __(
                    'Enables/Disables the Relist Action for the Listings, which use current Synchronization Policy.'
                ),
            ]
        );

        $fieldset->addField(
            'relist_filter_user_lock',
            self::SELECT,
            [
                'container_id' => 'relist_filter_user_lock_tr_container',
                'name' => 'relist_filter_user_lock',
                'label' => __('Relist When Stopped Manually'),
                'value' => $formData['relist_filter_user_lock'],
                'values' => [
                    1 => __('No'),
                    0 => __('Yes'),
                ],
                'tooltip' => __(
                    'Automatically Relists Item(s) even it has been Stopped manually within M2E Pro.'
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_synchronization_relist_rules',
            [
                'legend' => __('Relist Conditions'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'relist_status_enabled',
            self::SELECT,
            [
                'name' => 'relist_status_enabled',
                'label' => __('Product Status'),
                'value' => $formData['relist_status_enabled'],
                'values' => [
                    0 => __('Any'),
                    1 => __('Enabled'),
                ],
                'class' => 'M2ePro-validate-stop-relist-conditions-product-status',
                'tooltip' => __(
                    '<p><strong>Enabled:</strong> List Items on Amazon automatically if they have status ' .
                    'Enabled in Magento Product. (Recommended)</p>' .
                    '<p><strong>Any:</strong> List Items with any Magento Product status on Amazon automatically.</p>'
                ),
            ]
        );

        $fieldset->addField(
            'relist_is_in_stock',
            self::SELECT,
            [
                'name' => 'relist_is_in_stock',
                'label' => __('Stock Availability'),
                'value' => $formData['relist_is_in_stock'],
                'values' => [
                    0 => __('Any'),
                    1 => __('In Stock'),
                ],
                'class' => 'M2ePro-validate-stop-relist-conditions-stock-availability',
                'tooltip' => __(
                    '<p><strong>In Stock:</strong> List Items automatically if Products' .
                    ' are in Stock. (Recommended.)</p>' .
                    '<p><strong>Any:</strong> List Items automatically, regardless of Stock availability.</p>'
                ),
            ]
        );

        $form->addField(
            'relist_qty_calculated_confirmation_popup_template',
            self::CUSTOM_CONTAINER,
            [
                'text' => (string)__(
                    'Disabling this option might affect actual product data updates. ' .
                    'Please read <a href="%url" target="_blank">this article</a> before disabling the option.',
                    [
                        'url' => 'https://help.m2epro.com/support/solutions/articles/9000199813',
                    ]
                ),
                'style' => 'display: none;',
            ]
        );

        $fieldset->addField(
            'relist_qty_calculated',
            self::SELECT,
            [
                'name' => 'relist_qty_calculated',
                'label' => __('Quantity'),
                'value' => $formData['relist_qty_calculated'],
                'values' => [
                    TemplateSynchronization::QTY_MODE_NONE => __('Any'),
                    TemplateSynchronization::QTY_MODE_YES => __('More or Equal'),
                ],
                'class' => 'M2ePro-validate-stop-relist-conditions-item-qty',
                'tooltip' => __(
                    '<p><strong>Any:</strong> List Items automatically with any Quantity available.</p>' .
                    '<p><strong>More or Equal:</strong> List Items automatically if the Quantity is at ' .
                    'least equal to the number you set, according to the Selling Policy. (Recommended)</p>'
                ),
            ]
        )->setAfterElementHtml(
            <<<HTML
<input name="relist_qty_calculated_value" id="relist_qty_calculated_value"
       value="{$formData['relist_qty_calculated_value']}" type="text"
       style="width: 72px; margin-left: 10px;"
       class="input-text admin__control-text required-entry validate-digits _required" />
HTML
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_synchronization_relist_advanced_filters',
            [
                'legend' => __('Advanced Conditions'),
                'collapsable' => false,
                'tooltip' => __(
                    '<p>Define Magento Attribute value(s) based on which a product must be relisted on ' .
                    'the Channel.<br> Once both Relist Conditions and Advanced Conditions are met, ' .
                    'the product will be relisted.</p>'
                ),
            ]
        );

        $fieldset->addField(
            'relist_advanced_rules_filters_warning',
            self::MESSAGES,
            [
                'messages' => [
                    [
                        'type' => \Magento\Framework\Message\MessageInterface::TYPE_WARNING,
                        'content' => __(
                            'Please be very thoughtful before enabling this option as this functionality ' .
                            'can have a negative impact on the Performance of your system.<br> It can decrease ' .
                            'the speed of running in case you have a lot of Products with the high number ' .
                            'of changes made to them.'
                        ),
                    ],
                ],
            ]
        );

        $fieldset->addField(
            'relist_advanced_rules_mode',
            self::SELECT,
            [
                'name' => 'relist_advanced_rules_mode',
                'label' => __('Mode'),
                'value' => $formData['relist_advanced_rules_mode'],
                'values' => [
                    0 => __('Disabled'),
                    1 => __('Enabled'),
                ],
            ]
        );

        /** @var \Ess\M2ePro\Model\Magento\Product\Rule $ruleModel */
        $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule');
        $ruleModel->setData([
            'prefix' => Synchronization::RELIST_ADVANCED_RULES_PREFIX,
        ]);

        if (!empty($formData['relist_advanced_rules_filters'])) {
            $ruleModel->loadFromSerialized($formData['relist_advanced_rules_filters']);
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule $ruleBlock */
        $ruleBlock = $this
            ->getLayout()
            ->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule::class)
            ->setData(['rule_model' => $ruleModel]);

        $fieldset->addField(
            'advanced_filter',
            self::CUSTOM_CONTAINER,
            [
                'container_id' => 'relist_advanced_rules_filters_container',
                'label' => __('Conditions'),
                'text' => $ruleBlock->toHtml(),
            ]
        );

        $jsFormData = [
            'relist_mode',
            'relist_status_enabled',
            'relist_is_in_stock',

            'relist_qty_calculated',
            'relist_qty_calculated_value',

            'relist_advanced_rules_mode',
            'relist_advanced_rules_filters',
        ];

        foreach ($jsFormData as $item) {
            $this->js->add("M2ePro.formData.$item = '{$this->dataHelper->escapeJs($formData[$item])}';");
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
