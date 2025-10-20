<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Synchronization\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Amazon\Template\Synchronization;
use Ess\M2ePro\Model\Template\Synchronization as TemplateSynchronization;

class StopRules extends AbstractForm
{
    private \Ess\M2ePro\Helper\Module\Support $supportHelper;
    private \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper;
    private \Ess\M2ePro\Helper\Data $dataHelper;

    /**
     * @param \Ess\M2ePro\Helper\Module\Support $supportHelper
     * @param \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper
     * @param \Ess\M2ePro\Helper\Data $dataHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param array $data
     */
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
            'amazon_template_synchronization_stop',
            self::HELP_BLOCK,
            [
                'content' => __(
                    'Stop Rules define the Conditions when Amazon Items Listing must be ' .
                    'inactivated, depending on Magento Product state.<br/><br/>' .
                    '<b>Note:</b> If all Stop Conditions are set to <i>No</i> or <i>No Action</i>, ' .
                    'then the Stop Option for Amazon Items is disabled.<br/>' .
                    'If all Stop Conditions are enabled, then an Item will be inactivated if at least one of the ' .
                    'Stop Conditions is met.<br/><br/> ' .
                    'More detailed information about ability to work with this Page you can find ' .
                    '<a href="%url" target="_blank" class="external-link">here</a>.',
                    [
                        'url' => $this->supportHelper->getDocumentationArticleUrl('docs/stop-rules'),
                    ]
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_synchronization_stop_filters',
            [
                'legend' => __('General'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'stop_mode',
            self::SELECT,
            [
                'name' => 'stop_mode',
                'label' => __('Stop Action'),
                'value' => $formData['stop_mode'],
                'values' => [
                    0 => __('Disabled'),
                    1 => __('Enabled'),
                ],
                'tooltip' => __(
                    'Enable to automatically stop the Item(s) when the Stop Conditions are met.'
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_synchronization_stop_rules',
            [
                'legend' => __('Stop Conditions'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'stop_status_disabled',
            self::SELECT,
            [
                'name' => 'stop_status_disabled',
                'label' => __('Stop When Status Disabled'),
                'value' => $formData['stop_status_disabled'],
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'tooltip' => __(
                    'Automatically stops Item(s) if its status has been changed to \'Disabled\' in Magento.'
                ),
            ]
        );

        $fieldset->addField(
            'stop_out_off_stock',
            self::SELECT,
            [
                'name' => 'stop_out_off_stock',
                'label' => __('Stop When Out Of Stock'),
                'value' => $formData['stop_out_off_stock'],
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'tooltip' => __(
                    'Automatically stops Item(s) if its Stock availability has been changed to \'Out of Stock\'
                    in Magento.'
                ),
            ]
        );

        $form->addField(
            'stop_qty_calculated_confirmation_popup_template',
            self::CUSTOM_CONTAINER,
            [
                'text' => (string)__(
                    'Disabling this option might affect actual product data updates. Please read ' .
                    '<a href="%url" target="_blank">this article</a> before disabling the option.',
                    [
                        'url' => 'https://help.m2epro.com/support/solutions/articles/9000199813',
                    ]
                ),
                'style' => 'display: none;',
            ]
        );

        $fieldset->addField(
            'stop_qty_calculated',
            self::SELECT,
            [
                'name' => 'stop_qty_calculated',
                'label' => __('Stop When Quantity Is'),
                'value' => $formData['stop_qty_calculated'],
                'values' => [
                    TemplateSynchronization::QTY_MODE_NONE => __('No Action'),
                    TemplateSynchronization::QTY_MODE_YES => __('Less or Equal'),
                ],
                'tooltip' => __(
                    'Automatically stops Item(s) if Quantity according to the Selling ' .
                    'Policy has been changed and meets the Conditions.'
                ),
            ]
        )->setAfterElementHtml(
            <<<HTML
<input name="stop_qty_calculated_value" id="stop_qty_calculated_value"
       value="{$formData['stop_qty_calculated_value']}" type="text"
       style="width: 72px; margin-left: 10px;"
       class="input-text admin__control-text required-entry validate-digits _required" />
HTML
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_synchronization_stop_advanced_filters',
            [
                'legend' => __('Advanced Conditions'),
                'collapsable' => false,
                'tooltip' => __(
                    '<p>Define Magento Attribute value(s) based on which a product must be stopped on ' .
                    'the Channel.<br>Once at least one Stop or Advanced Condition is met, ' .
                    'the product will be stopped.</p>'
                ),
            ]
        );

        $fieldset->addField(
            'stop_advanced_rules_filters_warning',
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
            'stop_advanced_rules_mode',
            self::SELECT,
            [
                'name' => 'stop_advanced_rules_mode',
                'label' => __('Stop When Meet'),
                'value' => $formData['stop_advanced_rules_mode'],
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
            ]
        );

        /** @var \Ess\M2ePro\Model\Magento\Product\Rule $ruleModel */
        $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule');
        $ruleModel->setData([
            'prefix' => Synchronization::STOP_ADVANCED_RULES_PREFIX,
        ]);

        if (!empty($formData['stop_advanced_rules_filters'])) {
            $ruleModel->loadFromSerialized($formData['stop_advanced_rules_filters']);
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
                'container_id' => 'stop_advanced_rules_filters_container',
                'label' => __('Conditions'),
                'text' => $ruleBlock->toHtml(),
            ]
        );

        $jsFormData = [
            'stop_status_disabled',
            'stop_out_off_stock',

            'stop_qty_calculated',
            'stop_qty_calculated_value',

            'stop_advanced_rules_mode',
            'stop_advanced_rules_filters',
        ];

        foreach ($jsFormData as $item) {
            $this->js->add("M2ePro.formData.$item = '{$this->dataHelper->escapeJs($formData[$item])}';");
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
