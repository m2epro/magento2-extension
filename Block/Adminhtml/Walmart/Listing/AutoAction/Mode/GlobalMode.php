<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AutoAction\Mode;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AutoAction\Mode\GlobalMode
 */
class GlobalMode extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\GlobalMode
{
    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();

        $form->addField(
            'global_mode_help_block',
            self::HELP_BLOCK,
            [
                'content' => $this->__(
                    '<p>These Rules affect the whole Magento Catalog. When a new Product is added
                    to Magento Catalog, it will be automatically added to the current M2E Pro Listing if
                    the settings are enabled</p>
                    <p>Accordingly, if a Magento Product presented in the M2E Pro Listing is
                    removed from Magento Catalog, the Item will be removed from the Listing and
                    it will be stopped on Channel.</p>'
                )
            ]
        );

        $form->addField(
            'auto_mode',
            'hidden',
            [
                'name' => 'auto_mode',
                'value' => \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL
            ]
        );

        $fieldSet = $form->addFieldset('auto_global_fieldset_container', []);

        $fieldSet->addField(
            'auto_global_adding_mode',
            self::SELECT,
            [
                'name' => 'auto_global_adding_mode',
                'label' => $this->__('New Product Added to Magento'),
                'title' => $this->__('New Product Added to Magento'),
                'values' => [
                    ['value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE, 'label' => $this->__('No Action')],
                    ['value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_ADD, 'label' => $this->__('Add to the Listing')],
                ],
                'value' => $this->formData['auto_global_adding_mode'],
                'tooltip' => $this->__('Action which will be applied automatically.'),
                'style' => 'width: 350px'
            ]
        );

        $fieldSet->addField(
            'auto_global_adding_add_not_visible',
            self::SELECT,
            [
                'name' => 'auto_global_adding_add_not_visible',
                'label' => $this->__('Add not Visible Individually Products'),
                'title' => $this->__('Add not Visible Individually Products'),
                'values' => [
                    ['value' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_NO, 'label' => $this->__('No')],
                    [
                        'value' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
                        'label' => $this->__('Yes')
                    ],
                ],
                'value' => $this->formData['auto_global_adding_add_not_visible'],
                'field_extra_attributes' => 'id="auto_global_adding_add_not_visible_field"',
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
            'marketplace_id'        => $this->getListing()->getMarketplaceId(),
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
                'value' => $this->formData['auto_global_adding_category_template_id'],
                'field_extra_attributes' => 'id="auto_action_walmart_add_and_assign_category_template"',
                'required' => true,
                'after_element_html' => $this->getTooltipHtml($this->__(
                    'Select Category Policy you want to assign to Product(s).<br><br>
                    <strong>Note:</strong> Submitting of Category data is required when you create a new offer on
                    Walmart. Category Policy must be assigned to Products before they are added to M2E Pro Listing.'
                )) . '<a href="javascript: void(0);"
                        style="vertical-align: inherit; margin-left: 65px;"
                        onclick="ListingAutoActionObj.addNewTemplate(\''.$url.'\',
                        ListingAutoActionObj.reloadCategoryTemplates);">'.$this->__('Add New').'
                     </a>'
            ]
        );

        $fieldSet->addField(
            'auto_global_deleting_mode',
            self::SELECT,
            [
                'name' => 'auto_global_deleting_mode',
                'disabled' => 'disabled',
                'label' => $this->__('Product Deleted from Magento'),
                'title' => $this->__('Product Deleted from Magento'),
                'values' => [
                    ['value' => \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE,
                        'label' => $this->__('Stop on Channel and Delete from Listing')],
                ],
                'style' => 'width: 350px;'
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);

        return $this;
    }

    //########################################

    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Walmart\Listing::class)
        );

        $this->js->add(<<<JS

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
