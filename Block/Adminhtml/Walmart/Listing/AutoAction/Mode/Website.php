<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AutoAction\Mode;

class Website extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\AbstractWebsite
{
    private \Ess\M2ePro\Helper\Module\Support $supportHelper;
    private \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Magento\Store $magentoStoreHelper,
        array $data = []
    ) {
        $this->productTypeRepository = $productTypeRepository;
        $this->supportHelper = $supportHelper;
        parent::__construct(
            $context,
            $registry,
            $formFactory,
            $dataHelper,
            $globalDataHelper,
            $magentoStoreHelper,
            $data
        );
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $form->addField(
            'auto_mode_help_block',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    '<p>These Rules of automatic product adding and removal come into action when a Magento Product is
                    added to the Website with regard to the Store View selected for the M2E Pro Listing. In other
                    words, after a Magento Product is added to the selected Website, it can be automatically added to
                    M2E Pro Listing if the settings are enabled.</p><br>
                    <p>Please note if a product is already presented in another M2E Pro Listing with the related
                    Channel account and marketplace, the Item won’t be added to the Listing to prevent listing
                    duplicates on the Channel.</p><br>
                    <p>Accordingly, if a Magento Product presented in the M2E Pro Listing is removed from the Website,
                    the Item will be removed from the Listing and its sale will be stopped on Channel.</p><br>
                    <p>More detailed information you can find
                    <a href="%url%" target="_blank" class="external-link">here</a>.</p>',
                    $this->supportHelper->getDocumentationArticleUrl(
                        'adding-products-automatically-auto-addremove-rules'
                    )
                ),
            ]
        );

        $form->addField(
            'auto_mode',
            'hidden',
            [
                'name' => 'auto_mode',
                'value' => \Ess\M2ePro\Model\Listing::AUTO_MODE_WEBSITE,
            ]
        );

        $fieldSet = $form->addFieldset('auto_website_fieldset_container', []);

        $fieldSet->addField(
            'auto_website_adding_mode',
            self::SELECT,
            [
                'name' => 'auto_website_adding_mode',
                'label' => __('Product Added to Website'),
                'title' => __('Product Added to Website'),
                'values' => [
                    ['value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE, 'label' => __('No Action')],
                    ['value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_ADD, 'label' => __('Add to the Listing')],
                ],
                'value' => $this->formData['auto_website_adding_mode'],
                'tooltip' => __('Action which will be applied automatically.'),
                'style' => 'width: 350px',
            ]
        );

        $fieldSet->addField(
            'auto_website_adding_add_not_visible',
            self::SELECT,
            [
                'name' => 'auto_website_adding_add_not_visible',
                'label' => __('Add not Visible Individually Products'),
                'title' => __('Add not Visible Individually Products'),
                'values' => [
                    ['value' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_NO, 'label' => __('No')],
                    [
                        'value' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
                        'label' => __('Yes'),
                    ],
                ],
                'value' => $this->formData['auto_website_adding_add_not_visible'],
                'field_extra_attributes' => 'id="auto_website_adding_add_not_visible_field"',
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

        $productTypes = $this->productTypeRepository->retrieveByMarketplaceId(
            $this->getListing()->getMarketplaceId()
        );

        $options = [['label' => '', 'value' => '', 'attrs' => ['class' => 'empty']]];
        foreach ($productTypes as $productType) {
            $tmp = [
                'label' => $productType->getTitle(),
                'value' => $productType->getId(),
            ];

            $options[] = $tmp;
        }

        $url = $this->getUrl('*/walmart_productType/edit', [
            'marketplace_id' => $this->getListing()->getMarketplaceId(),
            'close_on_save' => true,
        ]);

        $fieldSet->addField(
            'adding_product_type_id',
            self::SELECT,
            [
                'name' => 'adding_product_type_id',
                'label' => __('Product Type'),
                'title' => __('Product Type'),
                'values' => $options,
                'value' => $this->formData['auto_website_adding_product_type_id'],
                'field_extra_attributes' => 'id="auto_action_walmart_add_and_assign_product_type"',
                'required' => true,
                'after_element_html' => $this->getTooltipHtml(
                    __(
                        'Select Product Type you want to assign to Product(s).<br><br>
                    <strong>Note:</strong> Submitting of Category data is required when you create a new offer on
                    Walmart. Product Type must be assigned to Products before they are added to M2E Pro Listing.'
                    )
                ) . '<a href="javascript: void(0);"
                        style="vertical-align: inherit; margin-left: 65px;"
                        onclick="ListingAutoActionObj.addNewTemplate(\'' . $url . '\',
                        ListingAutoActionObj.reloadProductTypes);">' . __('Add New') . '
                     </a>',
            ]
        );

        $fieldSet->addField(
            'auto_website_deleting_mode',
            self::SELECT,
            [
                'name' => 'auto_website_deleting_mode',
                'label' => __('Product Deleted from Website'),
                'title' => __('Product Deleted from Website'),
                'values' => [
                    [
                        'value' => \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE,
                        'label' => __('No Action'),
                    ],
                    [
                        'value' => \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP,
                        'label' => __('Stop on Channel'),
                    ],
                    [
                        'value' => \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE,
                        'label' => __('Stop on Channel and Delete from Listing'),
                    ],
                ],
                'value' => $this->formData['auto_website_deleting_mode'],
                'style' => 'width: 350px',
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return $this;
    }

    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Walmart\Listing::class)
        );

        $this->js->add(
            <<<JS

        $('adding_product_type_id').observe('change', function(el) {
            var options = $(el.target).select('.empty');
            options.length > 0 && options[0].hide();
        });
JS
        );

        return parent::_afterToHtml($html);
    }
}
