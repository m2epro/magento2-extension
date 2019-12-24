<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode\Category
 */
class Category extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\Category
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingAutoActionModeCategory');
        // ---------------------------------------
    }

    //########################################

    protected function prepareGroupsGrid()
    {
        $groupGrid = $this->createBlock('Ebay_Listing_AutoAction_Mode_Category_Group_Grid');
        $groupGrid->prepareGrid();
        $this->setChild('group_grid', $groupGrid);

        return $groupGrid;
    }

    //########################################

    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Ebay\Listing::class)
        );

        return parent::_afterToHtml($html);
    }

    protected function _toHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock')->setData([
            'content' => $this->__(
                <<<HTML
<p>You should combine Magento Categories into groups to apply the Auto Add/Remove Rules.
You can create as many groups as you need, but one Magento Category can be used
only in one Rule.</p><br>

<p>These Rules of automatic product adding and removal come into action when a Magento Product
is added to the Magento Category with regard to the Store View selected for the M2E Pro Listing.
In other words, after a Magento Product is added to the selected Magento Category,
it can be automatically added to M2E Pro Listing if the settings are enabled.</p><br>

<p>Accordingly, if a Magento Product present in the M2E Pro Listing is removed from
the Magento Category, the Item will be removed from the Listing and its sale will be
stopped on Channel.</p><br>

<p>More detailed information you can find
<a href="%url%" target="_blank" class="external-link">here</a>.</p>
HTML
                ,
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/-QItAQ')
            )
        ]);

        return $helpBlock->toHtml() . parent::_toHtml();
    }

    //########################################
}
