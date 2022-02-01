<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay\Registration
 */
class Registration extends InstallationEbay
{
    /** @var \Ess\M2ePro\Model\Registration\Manager */
    private $manager;

    public function __construct(
        \Ess\M2ePro\Model\Registration\Manager $manager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        Context $context
    ) {
        $this->manager = $manager;

        parent::__construct($ebayFactory, $nameBuilder, $context);
    }

    public function execute()
    {
        $this->init();

        return $this->registrationAction($this->manager);
    }
}
