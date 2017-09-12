<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\Category;

class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    protected $listing;

    public $formData = [];

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingAutoActionModeCategoryForm');
        // ---------------------------------------

        $this->formData = $this->getFormData();
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
                'tooltip' => $this->__(
                    'You need to provide additional settings for Magento Products to be listed automatically.'
                ),
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
        return parent::_prepareForm();
    }

    //########################################

    public function hasFormData()
    {
        return $this->getListing()->getData('auto_mode') == \Ess\M2ePro\Model\Listing::AUTO_MODE_CATEGORY;
    }

    public function getFormData()
    {
        $groupId = $this->getRequest()->getParam('group_id');
        $default = $this->getDefault();

        if (empty($groupId)) {
            return $default;
        }

        $group = $this->activeRecordFactory->getObjectLoaded(
            'Listing\Auto\Category\Group', $groupId
        );

        $data = $group->getData();
        $data = array_merge($data, $group->getChildObject()->getData());

        return array_merge($default, $data);
    }

    public function getDefault()
    {
        return array(
            'id' => NULL,
            'title' => NULL,
            'category_id' => NULL,
            'adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'deleting_mode' => \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE,
        );
    }

    //########################################

    public function getCategoriesFromOtherGroups()
    {
        $categories = $this->activeRecordFactory->getObject('Listing\Auto\Category\Group')->getResource()
            ->getCategoriesFromOtherGroups(
                $this->getRequest()->getParam('id'),
                $this->getRequest()->getParam('group_id')
            );

        foreach ($categories as &$group) {
            $group['title'] = $this->getHelper('Data')->escapeHtml($group['title']);
        }

        return $categories;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing
     * @throws \Exception
     */
    public function getListing()
    {
        if (is_null($this->listing)) {
            $listingId = $this->getRequest()->getParam('id');
            $this->listing = $this->activeRecordFactory->getCachedObjectLoaded('Listing', $listingId);
        }

        return $this->listing;
    }

    //########################################

    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants($this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Listing'));

        $magentoCategoryIdsFromOtherGroups = $this->getHelper('Data')->jsonEncode(
            $this->getCategoriesFromOtherGroups()
        );
        $this->js->add(<<<JS
            ListingAutoActionObj.magentoCategoryIdsFromOtherGroups = {$magentoCategoryIdsFromOtherGroups};

            jQuery('#'+ListingAutoActionObj.getPopupMode()+'continue_button')
                .off('click').on('click', ListingAutoActionObj.categoryStepTwo);
JS
        );

        return parent::_afterToHtml($html);
    }

    protected function _toHtml()
    {
        $selectedCategories = array();
        if ($this->getRequest()->getParam('group_id')) {
            $selectedCategories = $this->activeRecordFactory->getObject('Listing\Auto\Category')
                ->getCollection()
                ->addFieldToFilter('group_id', $this->getRequest()->getParam('group_id'))
                ->addFieldToFilter('category_id', array('neq' => 0))
                ->getColumnValues('category_id');
        }

        /** @var \Ess\M2ePro\Block\Adminhtml\Listing\Category\Tree $block */
        $block = $this->createBlock('Listing\Category\Tree');
        $block->setCallback('ListingAutoActionObj.magentoCategorySelectCallback');
        $block->setSelectedCategories($selectedCategories);

        $confirmMessage = <<<HTML
        <div id="dialog_confirm_content" style="display: none;">
            <div>
                {$this->__(
                    'This Category is already used in the Rule %s.
                    If you press "Confirm" Button, Category will be removed from that Rule.'
                )}
            </div>
        </div>
HTML;

        $this->css->add(
            'label.mage-error[for="validate_category_selection"] { width: 230px !important; left: 13px !important; }'
        );

        return '<div id="category_child_data_container">
                    <div id="category_tree_container">'.$block->toHtml().'</div>
                    <div id="category_form_container">'.parent::_toHtml().'</div>
                </div><div style="clear: both;"></div>
                <div><form id="validate_category_selection_form"><input type="hidden"
                            name="validate_category_selection"
                            id="validate_category_selection"
                            style="width: 255px;"
                            class="M2ePro-validate-category-selection" /></form>
                </div>' . $confirmMessage;
    }

    //########################################
}