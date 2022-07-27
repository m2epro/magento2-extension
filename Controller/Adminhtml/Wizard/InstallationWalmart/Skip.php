<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;
use Ess\M2ePro\Helper\Module\Wizard;

class Skip extends InstallationWalmart
{
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\View\Walmart $walmartViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $walmartViewHelper, $nameBuilder, $context);

        $this->magentoHelper = $magentoHelper;
    }

    public function execute()
    {
        $this->magentoHelper->clearMenuCache();
        $this->setStatus(Wizard::STATUS_SKIPPED);
        $this->_redirect("*/walmart_listing/index/");
    }
}
