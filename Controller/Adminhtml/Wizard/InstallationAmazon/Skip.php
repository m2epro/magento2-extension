<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;
use \Ess\M2ePro\Helper\Module\Wizard;

class Skip extends InstallationAmazon
{
    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\View\Amazon $amazonViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $amazonViewHelper, $nameBuilder, $context);

        $this->magentoHelper = $magentoHelper;
    }

    public function execute()
    {
        $this->magentoHelper->clearMenuCache();
        $this->setStatus(Wizard::STATUS_SKIPPED);
        $this->_redirect("*/amazon_listing/index/");
    }
}
