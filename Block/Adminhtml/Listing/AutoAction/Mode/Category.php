<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode;

class Category extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingAutoActionModeCategory');
        // ---------------------------------------
    }

    //########################################

    protected function _prepareForm()
    {
        $this->prepareGroupsGrid();

        $form = $this->_formFactory->create();

        $containerHtml = $this->getChildHtml('group_grid');

        $form->addField('custom_listing_auto_action_mode_category',
            'Ess\M2ePro\Block\Adminhtml\Magento\Form\Element\CustomContainer',
            [
                'text' => $containerHtml,
                'field_extra_attributes' => 'style="width: 100%"'
            ]
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

    protected function prepareGroupsGrid()
    {
        $groupGrid = $this->createBlock('Listing\AutoAction\Mode\Category\Group\Grid');
        $groupGrid->prepareGrid();
        $this->setChild('group_grid', $groupGrid);

        return $groupGrid;
    }

    //########################################

    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Listing')
        );

        // ---------------------------------------
        $groupGrid = $this->getChildBlock('group_grid');
        // ---------------------------------------

        $skipConfirmation = $this->getHelper('Data')->jsonEncode($groupGrid->getCollection()->getSize() == 0);
        $this->js->add(<<<JS
        var skipConfirmation = {$skipConfirmation};

        if (!skipConfirmation) {
            $('category_cancel_button').hide();
            $('category_close_button').hide();
            $('category_reset_button').show();
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
        return $this->__('Categories') ;
    }

    //########################################
}
