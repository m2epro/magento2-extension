<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\View;

class Group extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    private $listingProductId;

    private $motorsType;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('ebay/listing/view/settings/motors/view/group.phtml');
    }

    protected function _beforeToHtml()
    {
        //------------------------------
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\View\Group\Grid $block */
        $block = $this->createBlock('Ebay\Listing\View\Settings\Motors\View\Group\Grid');
        $block->setListingProductId($this->getListingProductId());
        $block->setMotorsType($this->getMotorsType());
        $this->setChild('view_group_grid', $block);
        //------------------------------

        return parent::_beforeToHtml();
    }

    //########################################

    /**
     * @return null
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getListingProductId()
    {
        if (is_null($this->listingProductId)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Listing Product ID was not set.');
        }

        return $this->listingProductId;
    }

    /**
     * @param null $listingProductId
     */
    public function setListingProductId($listingProductId)
    {
        $this->listingProductId = $listingProductId;
    }

    //########################################

    public function setMotorsType($motorsType)
    {
        $this->motorsType = $motorsType;
    }

    public function getMotorsType()
    {
        if (is_null($this->motorsType)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Motors type not set.');
        }

        return $this->motorsType;
    }

    //########################################
}