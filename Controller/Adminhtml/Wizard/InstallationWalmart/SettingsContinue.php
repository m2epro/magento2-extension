<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

class SettingsContinue extends InstallationWalmart
{
    /** @var \Ess\M2ePro\Helper\Component\Walmart\Configuration */
    private $configurationHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Walmart\Configuration $configurationHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\View\Walmart $walmartViewHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $walmartViewHelper, $nameBuilder, $context);

        $this->configurationHelper = $configurationHelper;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        if (empty($params)) {
            return $this->indexAction();
        }

        $this->configurationHelper->setConfigValues($params);

        $this->setStep($this->getNextStep());

        return $this->_redirect('*/*/installation');
    }
}
