<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Listing\View\Wizard;

use Ess\M2ePro\Model\Listing\Ui\RuntimeStorage;
use Ess\M2ePro\Helper\Magento\Store;

class Header extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var bool */
    protected $isListingViewMode = false;

    /** @var string */
    protected $_template = 'listing/view/header.phtml';

    private Store $magentoStoreHelper;

    private RuntimeStorage $runtimeStorage;

    public function __construct(
        Store $magentoStoreHelper,
        RuntimeStorage $runtimeStorage,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->magentoStoreHelper = $magentoStoreHelper;
        $this->runtimeStorage = $runtimeStorage;

        parent::__construct($context, $data);
    }

    public function isListingViewMode()
    {
        return $this->isListingViewMode;
    }

    public function setListingViewMode($mode)
    {
        $this->isListingViewMode = $mode;

        return $this;
    }

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
        $breadcrumb = $this->magentoStoreHelper->getStorePath($this->getListing()->getStoreId());

        return $cutLongValues ? $this->cutLongLines($breadcrumb) : $breadcrumb;
    }

    private function cutLongLines($line)
    {
        if (strlen($line) < 50) {
            return $line;
        }

        return substr($line, 0, 50) . '...';
    }

    private function getListing(): \Ess\M2ePro\Model\Listing
    {
        if (!$this->runtimeStorage->hasListing()) {
            throw new \LogicException('Listing was not initialized.');
        }

        return $this->runtimeStorage->getListing();
    }
}
