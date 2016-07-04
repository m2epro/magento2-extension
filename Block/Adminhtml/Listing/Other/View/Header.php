<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Other\View;

class Header extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    protected $_template = 'listing/other/view/header.phtml';

    //########################################

    public function getAccountTitle()
    {
        return $this->cutLongLines($this->getAccount()->getTitle());
    }

    public function getMarketplaceTitle()
    {
        return $this->cutLongLines($this->getMarketplace()->getTitle());
    }

    public function getStoreViewBreadcrumb()
    {
        if ($this->getAccount()->isComponentModeEbay()) {
            $relatedStoreId = $this->getAccount()->getChildObject()->getRelatedStoreId(
                $this->getMarketplace()->getId()
            );
        } else {
            $relatedStoreId = $this->getAccount()->getRelatedStoreId();
        }

        $breadcrumb = $this->getHelper('Magento\Store')->getStorePath($relatedStoreId);

        return $this->cutLongLines($breadcrumb);
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
     * @return \Ess\M2ePro\Model\Account
     */
    private function getAccount()
    {
        return $this->getData('account');
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    private function getMarketplace()
    {
        return $this->getData('marketplace');
    }

    //########################################
}