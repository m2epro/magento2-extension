<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AutoAction\Mode\Category;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AutoAction\Mode\Category\Form
 */
class Form extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\Category\Form
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartListingAutoActionModeCategoryForm');
        // ---------------------------------------
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(['data' => [
            'id' => 'edit_form',
        ]]);

        $form->addField(
            'group_id',
            'hidden',
            [
                'name' => 'id',
                'value' => $this->formData['id']
            ]
        );

        $form->addField(
            'auto_mode',
            'hidden',
            [
                'name' => 'auto_mode',
                'value' => \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY
            ]
        );

        $fieldSet = $form->addFieldset('category_form_container_field', []);

        $fieldSet->addField(
            'group_title',
            'text',
            [
                'name' => 'title',
                'label' => $this->__('Title'),
                'title' => $this->__('Title'),
                'class' => 'M2ePro-required-when-visible M2ePro-validate-category-group-title',
                'value' => $this->formData['title'],
                'required' => true
            ]
        );

        $fieldSet->addField(
            'adding_mode',
            'Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select',
            [
                'name' => 'adding_mode',
                'label' => $this->__('Product Assigned to Categories'),
                'title' => $this->__('Product Assigned to Categories'),
                'values' => [
                    ['label' => $this->__('No Action'), 'value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE],
                    ['label' => $this->__('Add to the Listing'), 'value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_ADD]
                ],
                'value' => $this->formData['adding_mode'],
                'tooltip' => $this->__('Action which will be applied automatically.'),
                'style' => 'width: 350px'
            ]
        );

        $fieldSet->addField(
            'adding_add_not_visible',
            'Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select',
            [
                'name' => 'adding_add_not_visible',
                'label' => $this->__('Add not Visible Individually Products'),
                'title' => $this->__('Add not Visible Individually Products'),
                'values' => [
                    ['label' => $this->__('No'), 'value' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_NO],
                    ['label' => $this->__('Yes'), 'value' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES]
                ],
                'value' => $this->formData['adding_add_not_visible'],
                'field_extra_attributes' => 'id="adding_add_not_visible_field"',
                'tooltip' => $this->__(
                    'Set to <strong>Yes</strong> if you want the Magento Products with
                    Visibility \'Not visible Individually\' to be added to the Listing
                    Automatically.<br/>
                    If set to <strong>No</strong>, only Variation (i.e.
                    Parent) Magento Products will be added to the Listing Automatically,
                    excluding Child Products.'
                )
            ]
        );

        $collection = $this->activeRecordFactory->getObject('Walmart_Template_Category')->getCollection();
        $collection->addFieldToFilter('marketplace_id', $this->getListing()->getMarketplaceId());

        $categoryTemplates = $collection->getData();

        $options = [['label' => '','value' => '', 'attrs' => ['class' => 'empty']]];
        foreach ($categoryTemplates as $template) {
            $tmp = [
                'label' => $this->escapeHtml($template['title']),
                'value' => $template['id']
            ];

            $options[] = $tmp;
        }

        $url = $this->getUrl('*/walmart_template_category/new', [
            'marketplace_id' => $this->getListing()->getMarketplaceId(),
            'close_on_save' => true
        ]);

        $fieldSet->addField(
            'adding_category_template_id',
            self::SELECT,
            [
                'name' => 'adding_category_template_id',
                'label' => $this->__('Category Policy'),
                'title' => $this->__('Category Policy'),
                'values' => $options,
                'value' => $this->formData['adding_category_template_id'],
                'field_extra_attributes' => 'id="auto_action_walmart_add_and_assign_category_template"',
                'required' => true,
                'after_element_html' => $this->getTooltipHtml($this->__(
                    'Select Category Policy you want to assign to Product(s).<br><br>
                    <strong>Note:</strong> Submitting of Category data is required when you create a new offer
                    on Walmart. Category Policy must be assigned to Products before they are added to M2E Pro Listing.'
                )) . '<a href="javascript: void(0);"
                    style="vertical-align: inherit; margin-left: 65px;"
                    onclick="ListingAutoActionObj.addNewTemplate(\''.$url.'\',
                    ListingAutoActionObj.reloadCategoryTemplates);">'.$this->__('Add New').'
                 </a>'
            ]
        );

        $fieldSet->addField(
            'deleting_mode',
            'Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select',
            [
                'name' => 'deleting_mode',
                'label' => $this->__('Product Deleted from Categories'),
                'title' => $this->__('Product Deleted from Categories'),
                'values' => [
                    ['label' => $this->__('No Action'), 'value' => \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE],
                    ['label' => $this->__('Stop on Channel'), 'value' => \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP],
                    [
                        'label' => $this->__('Stop on Channel and Delete from Listing'),
                        'value' => \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE
                    ],
                ],
                'value' => $this->formData['deleting_mode'],
                'style' => 'width: 350px'
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return $this;
    }

    //########################################

    public function getDefault()
    {
        return [
            'id' => null,
            'title' => null,
            'category_id' => null,
            'adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'deleting_mode' => \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE,
            'adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'adding_category_template_id' => null
        ];
    }

    //########################################

    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Walmart\Listing::class)
        );

        $this->js->add(<<<JS

        $('adding_mode')
            .observe('change', ListingAutoActionObj.addingModeChange)
            .simulate('change');

        $('adding_category_template_id').observe('change', function(el) {
            var options = $(el.target).select('.empty');
            options.length > 0 && options[0].hide();
        });
JS
        );

        return parent::_afterToHtml($html);
    }

    //########################################
}
