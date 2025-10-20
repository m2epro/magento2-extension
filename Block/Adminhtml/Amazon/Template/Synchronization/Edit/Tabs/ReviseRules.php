<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Synchronization\Edit\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm;

class ReviseRules extends AbstractForm
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
            'amazon_template_synchronization_revise',
            self::HELP_BLOCK,
            [
                'content' => __(
                    '<p>Specify which Channel data should be automatically revised by M2E Pro.</p><br>' .
                    '<p>Selected Item Properties will be automatically updated based on the changes in related ' .
                    'Magento Attributes or Policy Templates.</p><br><p>More detailed information on how to ' .
                    'work with this Page can be found <a href="%url" target="_blank" ' .
                    'class="external-link">here</a>.</p>',
                    [
                        'url' => $this->supportHelper->getDocumentationArticleUrl('docs/revise-rules'),
                    ]
                ),
            ]
        );

        $fieldset = $form->addFieldset(
            'magento_block_amazon_template_synchronization_form_data_revise_products',
            [
                'legend' => __('Revise Conditions'),
                'collapsable' => true,
            ]
        );

        $fieldset->addField(
            'revise_update_qty',
            self::SELECT,
            [
                'name' => 'revise_update_qty',
                'label' => __('Quantity'),
                'value' => $formData['revise_update_qty'],
                'values' => [
                    1 => __('Yes'),
                ],
                'disabled' => true,
                'tooltip' => __(
                    'Automatically revises Item Quantity, Production Time and Restock Date in Amazon Listing ' .
                    'when there are changes made in Magento to at least one mentioned parameter.'
                ),
            ]
        );

        $fieldset->addField(
            'revise_update_qty_max_applied_value_mode',
            self::SELECT,
            [
                'container_id' => 'revise_update_qty_max_applied_value_mode_tr',
                'name' => 'revise_update_qty_max_applied_value_mode',
                'label' => __('Conditional Revise'),
                'value' => $formData['revise_update_qty_max_applied_value_mode'],
                'values' => [
                    0 => __('Disabled'),
                    1 => __('Revise When Less or Equal to'),
                ],
                'tooltip' => __(
                    'Set the Item Quantity limit at which the Revise Action should be triggered. ' .
                    'It is recommended to keep this value relatively low, between 10 and 20 Items.'
                ),
            ]
        )->setAfterElementHtml(
            <<<HTML
<input name="revise_update_qty_max_applied_value" id="revise_update_qty_max_applied_value"
       value="{$formData['revise_update_qty_max_applied_value']}" type="text"
       style="width: 72px; margin-left: 10px;"
       class="input-text admin__control-text required-entry M2ePro-validate-qty _required" />
HTML
        );

        $fieldset->addField(
            'revise_update_qty_max_applied_value_line_tr',
            self::SEPARATOR,
            []
        );

        $fieldset->addField(
            'revise_update_price',
            self::SELECT,
            [
                'name' => 'revise_update_price',
                'label' => __('Price'),
                'value' => $formData['revise_update_price'],
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'tooltip' => __(
                    'Automatically revises Item Price, Minimum Advertised Price, Sale Price and Business Price ' .
                    'in Amazon Listing when there are changes made in Magento to at least one mentioned parameter.'
                ),
            ]
        );

        $fieldset->addField(
            'revise_update_main_details',
            self::SELECT,
            [
                'name' => 'revise_update_main_details',
                'label' => __('Main Product Details'),
                'value' => $formData['revise_update_main_details'],
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'tooltip' => __(
                    'Automatically revises Product Titles, Description, Bullet Points on Amazon when the ' .
                    'details are updated in Magento or Product Type.'
                ),
            ]
        );
        $fieldset->addField(
            'revise_update_images',
            self::SELECT,
            [
                'name' => 'revise_update_images',
                'label' => __('Images'),
                'value' => $formData['revise_update_images'],
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'tooltip' => __(
                    'Automatically revises Item Image(s) on Amazon when Product Image(s) or ' .
                    'Magento Attribute used for Product Image(s) are modified in Magento or Product Type.'
                ),
            ]
        );

        $fieldset->addField(
            'revise_update_details',
            self::SELECT,
            [
                'name' => 'revise_update_details',
                'label' => __('All Details'),
                'value' => $formData['revise_update_details'],
                'values' => [
                    0 => __('No'),
                    1 => __('Yes'),
                ],
                'tooltip' => __(
                    'Automatically revises Condition Note, Gift Message, Gift Wrap settings, data from ' .
                    'Product Type, List Price, Shipping Template Policy and Product Tax Code Policy in Amazon ' .
                    'Listing when there are changes made to Magento Attribute of at least one mentioned parameter.'
                ),
            ]
        );

        $form->addField(
            'revise_qty_max_applied_value_confirmation_popup_template',
            self::CUSTOM_CONTAINER,
            [
                'text' => (string)__(
                    '<br/>Disabling this option might affect synchronization performance. ' .
                    'Please read <a href="%url" target="_blank">this article</a> before using the option.',
                    [
                        'url' => 'https://help.m2epro.com/support/solutions/articles/9000200401',
                    ]
                ),
                'style' => 'display: none;',
            ]
        );

        $this->jsTranslator->add(
            'Wrong value. Only integer numbers.',
            __('Wrong value. Only integer numbers.')
        );

        $jsFormData = [
            'revise_update_qty',
            'revise_update_price',
            'revise_update_qty_max_applied_value',
        ];

        foreach ($jsFormData as $item) {
            $this->js->add("M2ePro.formData.$item = '{$this->dataHelper->escapeJs($formData[$item])}';");
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
