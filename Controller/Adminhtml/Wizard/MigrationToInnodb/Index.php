<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationToInnodb;

use Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationToInnodb;

class Index extends MigrationToInnodb
{
    /** @var \Ess\M2ePro\Helper\Component */
    private $componentHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component $componentHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($nameBuilder, $context);

        $this->componentHelper = $componentHelper;
    }

    public function execute()
    {
        if ($this->isNotStarted() || $this->isActive()) {
            $enabledComponents = $this->componentHelper->getEnabledComponents();
            $component = array_shift($enabledComponents);

            $this->getRequest()->getParam('referrer') && $component = $this->getRequest()->getParam('referrer');

            /** @var \Ess\M2ePro\Model\Wizard\MigrationToInnodb $wizard */
            $wizard = $this->getWizardHelper()->getWizard($this->getNick());
            $wizard->rememberRefererUrl($url = $this->getUrl("*/{$component}_listing/index"));
        }

        return $this->indexAction();
    }
}
