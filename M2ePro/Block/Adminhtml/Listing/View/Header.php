<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\View;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Listing\View\Header
 */
class Header extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    protected $isListingViewMode = false;
    protected $_template = 'listing/view/header.phtml';

    //########################################

    public function isListingViewMode()
    {
        return $this->isListingViewMode;
    }

    public function setListingViewMode($mode)
    {
        $this->isListingViewMode = $mode;
        return $this;
    }

    //########################################

    public function getComponent()
    {
        if ($this->getListing()->isComponentModeEbay()) {
            return $this->__('eBay');
        }

        if ($this->getListing()->isComponentModeAmazon()) {
            return $this->__('Amazon');
        }

        return '';
    }

    public function getProfileTitle()
    {
        return $this->cutLongLines($this->getListing()->getTitle());
    }

    public function getAccountTitle()
    {
        return $this->cutLongLines($this->getListing()->getAccount()->getTitle());
    }

    public function getMarketplaceTitle()
    {
        return $this->cutLongLines($this->getListing()->getMarketplace()->getTitle());
    }

    public function getStoreViewBreadcrumb($cutLongValues = true)
    {
        $breadcrumb = $this->getHelper('Magento\Store')->getStorePath($this->getListing()->getStoreId());

        return $cutLongValues ? $this->cutLongLines($breadcrumb) : $breadcrumb;
    }

    //########################################

    private function cutLongLines($line)
    {
        if (strlen($line) < 50) {
            return $line;
        }

        return substr($line, 0, 50) . '...';
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Listing
     */
    private function getListing()
    {
        return $this->getData('listing');
    }

    //########################################
}
