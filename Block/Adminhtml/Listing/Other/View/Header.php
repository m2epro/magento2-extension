<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Other\View;

class Header extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer
{
    /** @var string */
    protected $_template = 'listing/other/view/header.phtml';
    /** @var \Ess\M2ePro\Helper\Magento\Store */
    private $magentoStoreHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Magento\Store $magentoStoreHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->magentoStoreHelper = $magentoStoreHelper;
        parent::__construct($context, $data);
    }

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

        $breadcrumb = $this->magentoStoreHelper->getStorePath($relatedStoreId);

        return $this->cutLongLines($breadcrumb);
    }

    private function cutLongLines($line)
    {
        if (strlen($line) < 50) {
            return $line;
        }

        return substr($line, 0, 50) . '...';
    }

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    private function getAccount()
    {
        return $this->getData('account');
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    private function getMarketplace()
    {
        return $this->getData('marketplace');
    }
}
