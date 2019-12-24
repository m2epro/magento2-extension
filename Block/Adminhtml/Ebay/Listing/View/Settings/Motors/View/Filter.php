<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\View;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\View\Filter
 */
class Filter extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    private $listingProductId;

    private $motorsType;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('ebay/listing/view/settings/motors/view/filter.phtml');
    }

    protected function _beforeToHtml()
    {
        //------------------------------
        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\View\Filter\Grid $block */
        $block = $this->createBlock('Ebay_Listing_View_Settings_Motors_View_Filter_Grid');
        $block->setListingProductId($this->getListingProductId());
        $block->setMotorsType($this->getMotorsType());
        $this->setChild('view_filter_grid', $block);
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
        if ($this->listingProductId === null) {
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
        if ($this->motorsType === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Motors type not set.');
        }

        return $this->motorsType;
    }

    //########################################
}
