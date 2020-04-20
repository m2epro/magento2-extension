<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;

use Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart;
use Ess\M2ePro\Model\Walmart\Account as WalmartAccount;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Wizard\InstallationWalmart\EnableMarketplace
 */
class EnableMarketplace extends InstallationWalmart
{
    public function execute()
    {
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');

        if (empty($marketplaceId)) {
            $this->setAjaxContent('You should provide correct parameters.', false);

            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
        $marketplace = $this->walmartFactory->getCachedObjectLoaded(
            'Marketplace',
            $marketplaceId
        );
        $marketplace->setData('status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE)->save();

        $this->setJsonContent([
            'success' => true
        ]);
        return $this->getResult();
    }
}
