<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationToInnodb;

use Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationToInnodb;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationToInnodb\Index
 */
class Index extends MigrationToInnodb
{
    public function execute()
    {
        if ($this->isNotStarted() || $this->isActive()) {
            $enabledComponents = $this->getHelper('Component')->getEnabledComponents();
            $component = array_shift($enabledComponents);

            $this->getRequest()->getParam('referrer') && $component = $this->getRequest()->getParam('referrer');

            /** @var \Ess\M2ePro\Model\Wizard\MigrationToInnodb $wizard */
            $wizard = $this->getWizardHelper()->getWizard($this->getNick());
            $wizard->rememberRefererUrl($url = $this->getUrl("*/{$component}_listing/index"));
        }

        return $this->indexAction();
    }
}
