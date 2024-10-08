<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AutoAction\Mode\Category;

class Form extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\Category\AbstractForm
{
    private \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $dataHelper, $data);
        $this->productTypeRepository = $productTypeRepository;
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('walmartListingAutoActionModeCategoryForm');
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'id' => 'edit_form',
            ],
        ]);

        $form->addField(
            'group_id',
            'hidden',
            [
                'name' => 'id',
                'value' => $this->formData['id'],
            ]
        );

        $form->addField(
            'auto_mode',
            'hidden',
            [
                'name' => 'auto_mode',
                'value' => \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY,
            ]
        );

        $fieldSet = $form->addFieldset('category_form_container_field', []);

        $fieldSet->addField(
            'group_title',
            'text',
            [
                'name' => 'title',
                'label' => __('Title'),
                'title' => __('Title'),
                'class' => 'M2ePro-required-when-visible M2ePro-validate-category-group-title',
                'value' => $this->formData['title'],
                'required' => true,
            ]
        );

        $fieldSet->addField(
            'adding_mode',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select::class,
            [
                'name' => 'adding_mode',
                'label' => __('Product Assigned to Categories'),
                'title' => __('Product Assigned to Categories'),
                'values' => [
                    ['label' => __('No Action'), 'value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE],
                    ['label' => __('Add to the Listing'), 'value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_ADD],
                ],
                'value' => $this->formData['adding_mode'],
                'tooltip' => __('Action which will be applied automatically.'),
                'style' => 'width: 350px',
            ]
        );

        $fieldSet->addField(
            'adding_add_not_visible',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select::class,
            [
                'name' => 'adding_add_not_visible',
                'label' => __('Add not Visible Individually Products'),
                'title' => __('Add not Visible Individually Products'),
                'values' => [
                    ['label' => __('No'), 'value' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_NO],
                    [
                        'label' => __('Yes'),
                        'value' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
                    ],
                ],
                'value' => $this->formData['adding_add_not_visible'],
                'field_extra_attributes' => 'id="adding_add_not_visible_field"',
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

        $productTypeOptions = [['label' => '', 'value' => '', 'attrs' => ['class' => 'empty']]];
        foreach ($productTypes as $productType) {
            $tmp = [
                'label' => $productType->getTitle(),
                'value' => $productType->getId(),
            ];

            $productTypeOptions[] = $tmp;
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
                'values' => $productTypeOptions,
                'value' => $this->formData['adding_product_type_id'],
                'field_extra_attributes' => 'id="auto_action_walmart_add_and_assign_product_type"',
                'required' => true,
                'after_element_html' => $this->getTooltipHtml(
                    __(
                        'Select Product Type you want to assign to Product(s).<br><br>
                    <strong>Note:</strong> Submitting of Category data is required when you create a new offer
                    on Walmart. Product Type must be assigned to Products before they are added to M2E Pro Listing.'
                    )
                ) . '<a href="javascript: void(0);"
                    style="vertical-align: inherit; margin-left: 65px;"
                    onclick="ListingAutoActionObj.addNewTemplate(\'' . $url . '\',
                    ListingAutoActionObj.reloadCategoryTemplates);">' . __('Add New') . '
                 </a>',
            ]
        );

        $fieldSet->addField(
            'deleting_mode',
            \Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select::class,
            [
                'name' => 'deleting_mode',
                'label' => __('Product Deleted from Categories'),
                'title' => __('Product Deleted from Categories'),
                'values' => [
                    ['label' => __('No Action'), 'value' => \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE],
                    ['label' => __('Stop on Channel'), 'value' => \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP],
                    [
                        'label' => __('Stop on Channel and Delete from Listing'),
                        'value' => \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE,
                    ],
                ],
                'value' => $this->formData['deleting_mode'],
                'style' => 'width: 350px',
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return $this;
    }

    public function getDefault()
    {
        return [
            'id' => null,
            'title' => null,
            'category_id' => null,
            'adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'deleting_mode' => \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE,
            'adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'adding_product_type_id' => null,
        ];
    }

    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Walmart\Listing::class)
        );

        $this->js->add(
            <<<JS

        $('adding_mode')
            .observe('change', ListingAutoActionObj.addingModeChange)
            .simulate('change');

        $('adding_product_type_id').observe('change', function(el) {
            var options = $(el.target).select('.empty');
            options.length > 0 && options[0].hide();
        });
JS
        );

        return parent::_afterToHtml($html);
    }
}
