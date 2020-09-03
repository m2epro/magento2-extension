<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Product\Stop;

/**
 * Class \Ess\M2ePro\Model\Walmart\Connector\Product\Stop\Responser
 */
class Responser extends \Ess\M2ePro\Model\Walmart\Connector\Product\Responser
{
    /** @var \Ess\M2ePro\Model\Listing\Product $parentForProcessing */
    protected $parentForProcessing = null;

    //########################################

    protected function getSuccessfulMessage()
    {
        return 'Item was Stopped';
    }

    //########################################

    public function eventAfterExecuting()
    {
        if (!empty($this->params['params']['remove'])) {
            /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\RemoveHandler $removeHandler */
            $removeHandler = $this->modelFactory->getObject('Walmart_Listing_Product_RemoveHandler');
            $removeHandler->setListingProduct($this->listingProduct);
            $removeHandler->process();
        }

        parent::eventAfterExecuting();
    }

    protected function processParentProcessor()
    {
        if (empty($this->params['params']['remove'])) {
            parent::processParentProcessor();
            return;
        }

        if ($this->parentForProcessing === null) {
            return;
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $this->parentForProcessing->getChildObject();
        $walmartListingProduct->getVariationManager()->getTypeModel()->getProcessor()->process();
    }

    //########################################
}
