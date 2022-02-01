<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart\Registration
 */
class Registration extends InstallationWalmart
{
    /** @var \Ess\M2ePro\Model\Registration\Manager */
    private $manager;

    public function __construct(
        \Ess\M2ePro\Model\Registration\Manager $manager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        Context $context
    ) {
        $this->manager = $manager;

        parent::__construct($walmartFactory, $nameBuilder, $context);
    }

    public function execute()
    {
        $this->init();

        return $this->registrationAction($this->manager);
    }
}
