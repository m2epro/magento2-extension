<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode;

class GlobalMode extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\AbstractGlobalMode
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $supportHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        parent::__construct($context, $registry, $formFactory, $dataHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();
        $this->setId('ebayListingAutoActionModeGlobal');
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();
        $selectElementType = \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select::class;

        $form->addField(
            'auto_mode',
            'hidden',
            [
                'name' => 'auto_mode',
                'value' => \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL,
            ]
        );

        $fieldSet = $form->addFieldset('auto_global_fieldset_container', []);

        if ($this->formData['auto_global_adding_mode'] == \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE) {
            $fieldSet->addField(
                'auto_global_adding_mode',
                $selectElementType,
                [
                    'name' => 'auto_global_adding_mode',
                    'label' => __('New Product Added to Magento'),
                    'title' => __('New Product Added to Magento'),
                    'values' => [
                        \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE => __('No Action'),
                        \Ess\M2ePro\Model\Ebay\Listing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY => __(
                            'Add to the Listing and Assign eBay Category'
                        ),
                    ],
                    'value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
                    'tooltip' => __('Action which will be applied automatically.'),
                    'style' => 'width: 350px;',
                ]
            );
        } else {
            $fieldSet->addField(
                'auto_global_adding_mode',
                $selectElementType,
                [
                    'name' => 'auto_global_adding_mode',
                    'label' => __('New Product Added to Magento'),
                    'title' => __('New Product Added to Magento'),
                    'disabled' => true,
                    'values' => [
                        \Ess\M2ePro\Model\Ebay\Listing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY => __(
                            'Add to the Listing and Assign eBay Category'
                        ),
                    ],
                    'value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
                    'tooltip' => __('Action which will be applied automatically.'),
                    'style' => 'width: 350px;',
                ]
            );
        }

        $fieldSet->addField(
            'auto_global_adding_add_not_visible',
            $selectElementType,
            [
                'name' => 'auto_global_adding_add_not_visible',
                'label' => __('Add not Visible Individually Products'),
                'title' => __('Add not Visible Individually Products'),
                'values' => [
                    ['value' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_NO, 'label' => __('No')],
                    [
                        'value' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
                        'label' => __('Yes'),
                    ],
                ],
                'value' => $this->formData['auto_global_adding_add_not_visible'],
                'field_extra_attributes' => 'id="auto_global_adding_add_not_visible_field"',
                'tooltip' => __(
                    'Set to <strong>Yes</strong> if you want the Magento Products with
                    Visibility \'Not visible Individually\' to be added to the Listing
                    Automatically.<br/>
                    If set to <strong>No</strong>, only Variation (i.e.
                    Parent) Magento Products will be added to the Listing Automatically,
                    excluding Child Products.'
                ),
            ]
        );

        $fieldSet->addField(
            'auto_global_deleting_mode',
            $selectElementType,
            [
                'name' => 'auto_global_deleting_mode',
                'disabled' => true,
                'label' => __('Product Deleted from Magento'),
                'title' => __('Product Deleted from Magento'),
                'values' => [
                    [
                        'value' => \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE,
                        'label' => __('Stop on Channel and Delete from Listing'),
                    ],
                ],
                'style' => 'width: 350px;',
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Ebay\Listing::class)
        );

        return parent::_afterToHtml($html);
    }

    protected function _toHtml()
    {
        $helpBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\HelpBlock::class)->setData(
            [
                'content' => __(
                    '<p>These Rules of the automatic product adding and removal act globally for all Magento Catalog.
                    When a new Magento Product is added to Magento Catalog, it will be automatically added to the
                    current M2E Pro Listing if the settings are enabled.</p><br>
                    <p>Please note if a product is already presented in another M2E Pro Listing with the related Channel
                    account and marketplace, the Item won’t be added to the Listing to prevent listing duplicates on
                    the Channel.</p><br>
                    <p>Accordingly, if a Magento Product presented in the M2E Pro Listing is removed from Magento
                    Catalog, the Item will be removed from the Listing and its sale will be stopped on Channel.</p><br>
                    <p>More detailed information you can find
                    <a href="%url" target="_blank" class="external-link">here</a>.</p>',
                    ['url' => $this->supportHelper->getDocumentationArticleUrl('set-auto-add-remove-rules')]
                ),
            ]
        );

        return $helpBlock->toHtml() .
            parent::_toHtml() .
            '<div id="ebay_category_chooser"></div>';
    }
}
