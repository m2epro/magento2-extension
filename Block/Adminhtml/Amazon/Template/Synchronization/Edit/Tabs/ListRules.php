<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Synchronization\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;
use Ess\M2ePro\Model\Amazon\Template\Synchronization;
use Ess\M2ePro\Model\Template\Synchronization as TemplateSynchronization;

class ListRules extends AbstractForm
{
    private \Ess\M2ePro\Helper\Module\Support $supportHelper;
    private \Ess\M2ePro\Helper\Data $dataHelper;
    private \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        $this->dataHelper = $dataHelper;
        $this->globalDataHelper = $globalDataHelper;
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
            'amazon_template_synchronization_general',
            self::HELP_BLOCK,
            [
                'content' => __(
                    '<p>If <strong>List Action</strong> is enabled, each new Item in ' .
                    'M2E Pro Listing, that has Not Listed status and the settings met specified List Conditions, ' .
                    'will be listed automatically</p><br/><p><strong>Note:</strong> M2E Pro Listings ' .
                    'Synchronization must be enabled <strong>(Amazon Integration > Configuration > ' .
                    'Settings > Synchronization)</strong>. Otherwise, Synchronization Policy Rules will not ' .
                    'take effect.</p><br/><p>More detailed information about how to work with this Page you can find ' .
                    '<a href="%url" target="_blank" class="external-link">here</a>.</p>',
                    [
                        'url' => $this->supportHelper->getDocumentationArticleUrl('docs/amazon-list-rules/'),
                    ]
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_synchronization_general_list',
            [
                'legend' => __('General'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'list_mode',
            self::SELECT,
            [
                'name' => 'list_mode',
                'label' => __('List Action'),
                'value' => $formData['list_mode'],
                'values' => [
                    0 => __('Disabled'),
                    1 => __('Enabled'),
                ],
                'tooltip' => __(
                    'Enables / disables automatic Listing of <i>Not Listed</i> Items, ' .
                    'when they meet the List Conditions.'
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_synchronization_list_rules',
            [
                'legend' => __('List Conditions'),
                'collapsable' => false,
            ]
        );

        $fieldset->addField(
            'list_status_enabled',
            self::SELECT,
            [
                'name' => 'list_status_enabled',
                'label' => __('Product Status'),
                'value' => $formData['list_status_enabled'],
                'values' => [
                    0 => __('Any'),
                    1 => __('Enabled'),
                ],
                'tooltip' => __(
                    '<p><strong>Enabled:</strong> List Items on Amazon automatically if they ' .
                    'have status Enabled in Magento Product. (Recommended)</p><p><strong>Any:</strong> ' .
                    'List Items with any Magento Product status on Amazon automatically.</p>'
                ),
            ]
        );

        $fieldset->addField(
            'list_is_in_stock',
            self::SELECT,
            [
                'name' => 'list_is_in_stock',
                'label' => __('Stock Availability'),
                'value' => $formData['list_is_in_stock'],
                'values' => [
                    0 => __('Any'),
                    1 => __('In Stock'),
                ],
                'tooltip' => __(
                    '<p><strong>In Stock:</strong> List Items automatically if ' .
                    'Products are in Stock. (Recommended.)</p><p><strong>Any:</strong> List Items automatically, ' .
                    'regardless of Stock availability.</p>'
                ),
            ]
        );

        $fieldset->addField(
            'list_qty_calculated',
            self::SELECT,
            [
                'name' => 'list_qty_calculated',
                'label' => __('Quantity'),
                'value' => $formData['list_qty_calculated'],
                'values' => [
                    TemplateSynchronization::QTY_MODE_NONE => __('Any'),
                    TemplateSynchronization::QTY_MODE_YES => __('More or Equal'),
                ],
                'tooltip' => __(
                    '<p><strong>Any:</strong> List Items automatically with any ' .
                    'Quantity available.</p><p><strong>More or Equal:</strong> List Items automatically ' .
                    'if the Quantity is at least equal to the number you set, according to the Selling Policy. ' .
                    '(Recommended)</p>'
                ),
            ]
        )->setAfterElementHtml(
            <<<HTML
<input name="list_qty_calculated_value" id="list_qty_calculated_value"
       value="{$formData['list_qty_calculated_value']}" type="text"
       style="width: 72px; margin-left: 10px;"
       class="input-text admin__control-text required-entry validate-digits _required" />
HTML
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_synchronization_list_advanced_filters',
            [
                'legend' => __('Advanced Conditions'),
                'collapsable' => false,
                'tooltip' => __(
                    '<p>Define Magento Attribute value(s) based on which a product must be listed ' .
                    'on the Channel.<br>Once both List Conditions and Advanced Conditions are met, ' .
                    'the product will be listed.</p>'
                ),
            ]
        );

        $fieldset->addField(
            'list_advanced_rules_filters_warning',
            self::MESSAGES,
            [
                'messages' => [
                    [
                        'type' => \Magento\Framework\Message\MessageInterface::TYPE_WARNING,
                        'content' => __(
                            'Please be very thoughtful before enabling this option as this functionality ' .
                            'can have a negative impact on the Performance of your system.<br>' .
                            'It can decrease the speed of running in case you have a lot of Products with the ' .
                            'high number of changes made to them.'
                        ),
                    ],
                ],
            ]
        );

        $fieldset->addField(
            'list_advanced_rules_mode',
            self::SELECT,
            [
                'name' => 'list_advanced_rules_mode',
                'label' => __('Mode'),
                'value' => $formData['list_advanced_rules_mode'],
                'values' => [
                    0 => __('Disabled'),
                    1 => __('Enabled'),
                ],
            ]
        );

        /** @var \Ess\M2ePro\Model\Magento\Product\Rule $ruleModel */
        $ruleModel = $this->activeRecordFactory->getObject('Magento_Product_Rule');
        $ruleModel->setData([
            'prefix' => Synchronization::LIST_ADVANCED_RULES_PREFIX
        ]);

        if (!empty($formData['list_advanced_rules_filters'])) {
            $ruleModel->loadFromSerialized($formData['list_advanced_rules_filters']);
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
                'container_id' => 'list_advanced_rules_filters_container',
                'label' => __('Conditions'),
                'text' => $ruleBlock->toHtml(),
            ]
        );

        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Template\Synchronization::class)
        );
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Amazon\Template\Synchronization::class)
        );
        $this->jsPhp->addConstants(
            $this->dataHelper
                ->getClassConstants(\Ess\M2ePro\Helper\Component\Amazon::class)
        );

        $this->jsUrl->addUrls([
            'formSubmit' => $this->getUrl(
                '*/amazon_template_synchronization/save',
                ['_current' => true]
            ),
            'formSubmitNew' => $this->getUrl('m2epro/amazon_template_synchronization/save'),
            'deleteAction' => $this->getUrl(
                '*/amazon_template_synchronization/delete',
                ['_current' => true]
            ),
        ]);

        $this->jsTranslator->addTranslations([
            'Add Synchronization Policy' => __('Add Synchronization Policy'),
            'Wrong time format string.' => __('Wrong time format string.'),

            'Must be greater than "Min".' => __('Must be greater than "Min".'),
            'Inconsistent Settings in Relist and Stop Rules.' => __(
                'Inconsistent Settings in Relist and Stop Rules.'
            ),

            'The specified Title is already used for other Policy. Policy Title must be unique.' => __(
                'The specified Title is already used for other Policy. Policy Title must be unique.'
            ),
        ]);

        $this->js->add("M2ePro.formData.id = '{$this->getRequest()->getParam('id')}';");
        $this->js->add(
            "M2ePro.formData.title
            = '{$this->dataHelper->escapeJs($this->dataHelper->escapeHtml($formData['title']))}';"
        );

        $this->js->add(
            <<<JS
    require([
        'M2ePro/Amazon/Template/Synchronization',
    ], function(){
        window.AmazonTemplateSynchronizationObj = new AmazonTemplateSynchronization();
        AmazonTemplateSynchronizationObj.initObservers();
    });
JS
        );

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
