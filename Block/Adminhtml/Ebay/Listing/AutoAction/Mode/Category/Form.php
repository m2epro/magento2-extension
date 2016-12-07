<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode\Category;

class Form extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\Category\Form
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingAutoActionModeCategoryForm');
        // ---------------------------------------
    }

    protected function _prepareForm()
    {
        parent::_prepareForm();
        $form = $this->getForm();

        $addingMode = $form->getElement('adding_mode');
        $addingMode->addElementValues([
            \Ess\M2ePro\Model\Ebay\Listing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY => $this->__(
                'Add to the Listing and Assign eBay Category'
            )
        ]);

        return $this;
    }

    //########################################

    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants('\Ess\M2ePro\Model\Ebay\Listing')
        );

        $this->js->add(<<<JS
            $('adding_mode')
                .observe('change', ListingAutoActionObj.categoryAddingMode)
                .simulate('change');
JS
        );

        return parent::_afterToHtml($html);
    }

    //########################################

    protected function _toHtml()
    {
        $breadcrumb = $this->createBlock('Ebay\Listing\AutoAction\Mode\Breadcrumb', '', ['data' => [
            'id_prefix' => 'category'
        ]]);
        $breadcrumb->setSelectedStep(1);

        return $breadcrumb->toHtml() . parent::_toHtml();
    }

    //########################################
}
