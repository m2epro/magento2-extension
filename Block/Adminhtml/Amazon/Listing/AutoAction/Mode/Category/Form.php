<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\AutoAction\Mode\Category;

class Form extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\Category\Form
{
    public $showCreateNewAsin = 0;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingAutoActionModeCategoryForm');
        // ---------------------------------------
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create(['data' => [
            'id' => 'edit_form',
        ]]);

        $form->addField('group_id', 'hidden',
            [
                'name' => 'id',
                'value' => $this->formData['id']
            ]
        );

        $form->addField('auto_mode', 'hidden',
            [
                'name' => 'auto_mode',
                'value' => \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY
            ]
        );

        $fieldSet = $form->addFieldset('category_form_container_field', []);

        $fieldSet->addField('group_title', 'text',
            [
                'name' => 'title',
                'label' => $this->__('Title'),
                'title' => $this->__('Title'),
                'class' => 'M2ePro-required-when-visible M2ePro-validate-category-group-title',
                'value' => $this->formData['title'],
                'required' => true
            ]
        );

        $fieldSet->addField('adding_mode',
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

        $fieldSet->addField('adding_add_not_visible',
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

        $fieldSet->addField('auto_action_create_asin',
            self::SELECT,
            [
                'name' => 'auto_action_create_asin',
                'label' => $this->__('Create New ASIN / ISBN if not found'),
                'title' => $this->__('Create New ASIN / ISBN if not found'),
                'values' => [
                    [
                        'value' => \Ess\M2ePro\Model\Amazon\Listing::ADDING_MODE_ADD_AND_CREATE_NEW_ASIN_NO,
                        'label' => $this->__('No')
                    ],
                    [
                        'value' => \Ess\M2ePro\Model\Amazon\Listing::ADDING_MODE_ADD_AND_CREATE_NEW_ASIN_YES,
                        'label' => $this->__('Yes')
                    ],
                ],
                'value' => (int)!empty($this->formData['adding_description_template_id']),
                'field_extra_attributes' => 'id="auto_action_amazon_add_and_create_asin"',
                'tooltip' => $this->__(
                    'Should M2E Pro try to create new ASIN/ISBN in case Search
                    Settings are not set or contain the incorrect values?'
                )
            ]
        );

        $collection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK, 'Template\Description'
        )->getCollection();
        $collection->addFieldToFilter('marketplace_id', $this->getListing()->getMarketplaceId());

        $descriptionTemplates = $collection->getData();

        if (count($descriptionTemplates) > 0) {
            $this->showCreateNewAsin = 1;
        }

        usort($descriptionTemplates, function($a, $b) {
            return $a["is_new_asin_accepted"] < $b["is_new_asin_accepted"];
        });

        $options = [['label' => '','value' => '', 'attrs' => ['class' => 'empty']]];
        foreach($descriptionTemplates as $template) {
            $tmp = [
                'label' => $this->escapeHtml($template['title']),
                'value' => $template['id']
            ];

            if (!$template['is_new_asin_accepted']) {
                $tmp['attrs'] = ['disabled' => 'disabled'];
            }

            $options[] = $tmp;
        }

        $url = $this->getUrl('*/amazon_template_description/new', array(
            'is_new_asin_accepted'  => 1,
            'marketplace_id'        => $this->getListing()->getMarketplaceId(),
            'close_on_save' => true
        ));

        $fieldSet->addField('adding_description_template_id',
            self::SELECT,
            [
                'name' => 'adding_description_template_id',
                'label' => $this->__('Description Policy'),
                'title' => $this->__('Description Policy'),
                'values' => $options,
                'value' => $this->formData['adding_description_template_id'],
                'field_extra_attributes' => 'id="auto_action_amazon_add_and_assign_description_template"',
                'required' => true,
                'after_element_html' => $this->getTooltipHtml($this->__(
                    'Creation of new ASIN/ISBN will be performed based on specified Description Policy.
                    Only the Description Policies set for new ASIN/ISBN creation are available for choosing.
                    <br/><br/><b>Note:</b> If chosen Description Policy doesn’t meet all the
                    Conditions for new ASIN/ISBN creation, the Products will still be added to M2E Pro Listings
                    but will not be Listed on Amazon.'
                                    )) . '<a href="javascript: void(0);"
                                            style="vertical-align: inherit; margin-left: 65px;"
                                            onclick="ListingAutoActionObj.addNewTemplate(\''.$url.'\',
                                            ListingAutoActionObj.reloadDescriptionTemplates);">'.$this->__('Add New').'
                                         </a>'
            ]
        );

        $fieldSet->addField('deleting_mode',
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
        return array(
            'id' => NULL,
            'title' => NULL,
            'category_id' => NULL,
            'adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'deleting_mode' => \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE,
            'adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'adding_description_template_id' => NULL
        );
    }

    //########################################

    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Amazon\Listing')
        );

        $this->js->add(<<<JS

        ListingAutoActionObj.showCreateNewAsin = {$this->showCreateNewAsin};

        $('adding_mode')
            .observe('change', ListingAutoActionObj.addingModeChange)
            .simulate('change');

        $('auto_action_create_asin')
            .observe('change', ListingAutoActionObj.createAsinChange)
            .simulate('change');

        $('adding_description_template_id').observe('change', function(el) {
            var options = $(el.target).select('.empty');
            options.length > 0 && options[0].hide();
        });
JS
        );

        return parent::_afterToHtml($html);
    }

    //########################################
}
