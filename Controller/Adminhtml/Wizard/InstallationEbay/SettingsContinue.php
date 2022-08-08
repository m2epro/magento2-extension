<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationEbay;

class SettingsContinue extends InstallationEbay
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Configuration */
    private $configuration;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Configuration $configuration,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\View\Ebay $ebayViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        Context $context
    ) {
        parent::__construct($ebayFactory, $ebayViewHelper, $nameBuilder, $context);

        $this->configuration = $configuration;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if (empty($params)) {
            return $this->indexAction();
        }

        $this->configuration->setConfigValues($params);
        $this->setStep($this->getNextStep());

        return $this->_redirect('*/*/installation');
    }
}
