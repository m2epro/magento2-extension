<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;
use Ess\M2ePro\Helper\Module\Wizard;

class Skip extends InstallationEbay
{
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $ebayViewHelper, $nameBuilder, $context);

        $this->magentoHelper = $magentoHelper;
    }

    public function execute()
    {
        $this->magentoHelper->clearMenuCache();
        $this->setStatus(Wizard::STATUS_SKIPPED);
        $this->_redirect("*/ebay_listing/index/");
    }
}
