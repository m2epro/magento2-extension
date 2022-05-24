<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

class Registration extends InstallationWalmart
{
    /** @var \Ess\M2ePro\Model\Registration\Manager */
    private $manager;

    public function __construct(
        \Ess\M2ePro\Model\Registration\Manager $manager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\View\Walmart $walmartViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        Context $context
    ) {
        parent::__construct($walmartFactory, $walmartViewHelper, $nameBuilder, $context);
        $this->manager = $manager;
    }

    public function execute()
    {
        $this->init();

        return $this->registrationAction($this->manager);
    }
}
