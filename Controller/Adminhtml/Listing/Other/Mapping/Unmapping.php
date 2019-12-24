<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Other\Mapping;

use Ess\M2ePro\Controller\Adminhtml\Listing;
use Ess\M2ePro\Controller\Adminhtml\Context;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Listing\Other\Mapping\Unmapping
 */
class Unmapping extends Listing
{
    public function execute()
    {
        $componentMode = $this->getRequest()->getParam('componentMode');
        $productIds = $this->getRequest()->getParam('product_ids');

        if (!$productIds || !$componentMode) {
            $this->setAjaxContent('0', false);
            return $this->getResult();
        }

        $productArray = explode(',', $productIds);

        if (empty($productArray)) {
            $this->setAjaxContent('0', false);
            return $this->getResult();
        }

        foreach ($productArray as $productId) {
            $listingOtherProductInstance = $this->parentFactory->getObjectLoaded(
                $componentMode,
                'Listing\Other',
                $productId
            );

            if (!$listingOtherProductInstance->getId() ||
                $listingOtherProductInstance->getData('product_id') === null) {
                continue;
            }

            $listingOtherProductInstance->unmapProduct(\Ess\M2ePro\Helper\Data::INITIATOR_USER);
        }

        $this->setAjaxContent('1', false);
        return $this->getResult();
    }
}
