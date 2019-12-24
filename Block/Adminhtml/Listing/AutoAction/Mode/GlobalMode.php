<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\GlobalMode
 */
class GlobalMode extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    protected $listing;

    public $formData = [];

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingAutoActionModeGlobal');
        // ---------------------------------------

        $this->formData = $this->getFormData();
    }

    //########################################

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create();
        $selectElementType = 'Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\Select';

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
            $selectElementType,
            [
                'name' => 'auto_global_adding_mode',
                'label' => $this->__('New Product Added to Magento'),
                'title' => $this->__('New Product Added to Magento'),
                'values' => [
                    ['value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE, 'label' => $this->__('No Action')],
                    ['value' => \Ess\M2ePro\Model\Listing::ADDING_MODE_ADD, 'label' => $this->__('Add to the Listing')],
                ],
                'value' => $this->formData['auto_global_adding_mode'],
                'tooltip' => $this->__(
                    'You need to provide additional settings for Magento Products to be listed automatically.'
                ),
                'style' => 'width: 350px;'
            ]
        );

        $fieldSet->addField(
            'auto_global_adding_add_not_visible',
            $selectElementType,
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

        $fieldSet->addField(
            'auto_global_deleting_mode',
            $selectElementType,
            [
                'name' => 'auto_global_deleting_mode',
                'disabled' => true,
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

        return parent::_prepareForm();
    }

    //########################################

    public function hasFormData()
    {
        return $this->getListing()->getData('auto_mode') == \Ess\M2ePro\Model\Listing::AUTO_MODE_GLOBAL;
    }

    public function getFormData()
    {
        $formData = $this->getListing()->getData();
        $formData = array_merge($formData, $this->getListing()->getChildObject()->getData());
        $default = $this->getDefault();
        return array_merge($default, $formData);
    }

    public function getDefault()
    {
        return [
            'auto_global_adding_mode' => \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE,
            'auto_global_adding_add_not_visible' => \Ess\M2ePro\Model\Listing::AUTO_ADDING_ADD_NOT_VISIBLE_YES,
            'auto_global_deleting_mode' => \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE,
        ];
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing
     * @throws \Exception
     */
    public function getListing()
    {
        if ($this->listing === null) {
            $listingId = $this->getRequest()->getParam('id');
            $this->listing = $this->activeRecordFactory->getCachedObjectLoaded(
                'Listing',
                $listingId
            );
        }

        return $this->listing;
    }

    //########################################

    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Listing::class)
        );

        $hasFormData = $this->hasFormData() ? 'true' : 'false';

        $this->js->add(<<<JS
        $('auto_global_adding_mode')
            .observe('change', ListingAutoActionObj.addingModeChange)
            .simulate('change');

        if ({$hasFormData}) {
            $('global_reset_button').show();
        }
JS
        );

        return parent::_afterToHtml($html);
    }

    protected function _toHtml()
    {
        return '<div id="additional_autoaction_title_text" style="display: none">' . $this->getBlockTitle() . '</div>'
                . '<div id="block-content-wrapper"><div id="data_container">'.parent::_toHtml().'</div></div>';
    }

    // ---------------------------------------

    protected function getBlockTitle()
    {
        return $this->__('Global all Products');
    }

    //########################################
}
