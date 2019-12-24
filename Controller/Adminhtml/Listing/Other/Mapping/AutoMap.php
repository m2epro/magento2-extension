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
 * Class \Ess\M2ePro\Controller\Adminhtml\Listing\Other\Mapping\AutoMap
 */
class AutoMap extends Listing
{
    public function execute()
    {
        $componentMode = $this->getRequest()->getParam('componentMode');
        $productIds = $this->getRequest()->getParam('product_ids');

        if (empty($productIds)) {
            $this->setAjaxContent('You should select one or more Products', false);
            return $this->getResult();
        }

        if (empty($componentMode)) {
            $this->setAjaxContent('Component is not defined.', false);
            return $this->getResult();
        }

        $productIds = explode(',', $productIds);

        $productsForMapping = [];
        foreach ($productIds as $productId) {

            /** @var $listingOther \Ess\M2ePro\Model\Listing\Other */
            $listingOther = $this->parentFactory
                ->getObjectLoaded($componentMode, 'Listing\Other', $productId);

            if ($listingOther->getProductId()) {
                continue;
            }

            $productsForMapping[] = $listingOther;
        }

        $componentMode = ucfirst(strtolower($componentMode));
        $mappingModel = $this->modelFactory->getObject($componentMode.'\Listing\Other\Mapping');
        $mappingModel->initialize();

        if (!$mappingModel->autoMapOtherListingsProducts($productsForMapping)) {
            $this->setAjaxContent('1', false);
            return $this->getResult();
        }
    }
}
