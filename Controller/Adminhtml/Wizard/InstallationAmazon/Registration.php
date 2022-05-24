<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationAmazon;

class Registration extends InstallationAmazon
{
    /** @var \Ess\M2ePro\Model\Registration\Manager */
    private $manager;

    public function __construct(
        \Ess\M2ePro\Model\Registration\Manager $manager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Helper\View\Amazon $amazonViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        Context $context
    ) {
        parent::__construct($amazonFactory, $amazonViewHelper, $nameBuilder, $context);
        $this->manager = $manager;
    }

    public function execute()
    {
        $this->init();

        return $this->registrationAction($this->manager);
    }
}
