<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AutoAction\Mode;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Walmart\Listing\AutoAction\Mode\Category
 */
class Category extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\Category
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartListingAutoActionModeCategory');
        // ---------------------------------------
    }

    //########################################

    protected function prepareGroupsGrid()
    {
        $groupGrid = $this->createBlock('Walmart_Listing_AutoAction_Mode_Category_Group_Grid');
        $groupGrid->prepareGrid();
        $this->setChild('group_grid', $groupGrid);

        return $groupGrid;
    }

    //########################################

    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Walmart\Listing::class)
        );

        return parent::_afterToHtml($html);
    }

    protected function _toHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock')->setData([
            'content' => $this->__(
                '<p>You can combine Magento Categories into a group to apply the Auto Add/Remove Rules.
                You can create as many groups as you need, but one Magento Category
                can be used only in one Rule.</p><br>
                <p>These Rules come into action when a Magento Product is added to the Magento Category with
                regard to the Store View selected for the M2E Pro Listing. In other words, after a Magento Product
                is added to the selected Magento Category, it can be automatically added to M2E Pro Listing if
                the settings are enabled.</p><br>
                <p>Accordingly, if a Magento Product presented in the M2E Pro Listing is removed from the
                Magento Category, the Item will be removed from the Listing and it will be stopped on Channel.</p>'
            )
        ]);

        return $helpBlock->toHtml() . parent::_toHtml();
    }

    //########################################
}
