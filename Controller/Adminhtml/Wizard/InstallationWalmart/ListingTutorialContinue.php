<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart\ListingTutorialContinue
 */
class ListingTutorialContinue extends InstallationWalmart
{
    public function execute()
    {
        /** @var \Ess\M2ePro\Helper\Module\Wizard $wizardHelper */
        $wizardHelper = $this->getHelper('Module\Wizard');

        $wizardHelper->setStatus(
            \Ess\M2ePro\Helper\View\Walmart::WIZARD_INSTALLATION_NICK,
            \Ess\M2ePro\Helper\Module\Wizard::STATUS_COMPLETED
        );

        $this->getHelper('Magento')->clearMenuCache();

        return $this->_redirect('*/walmart_listing_create');
    }
}
