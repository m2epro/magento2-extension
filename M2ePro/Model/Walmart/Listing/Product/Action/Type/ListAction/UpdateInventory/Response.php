<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction\UpdateInventory;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction\UpdateInventory\Response
 */
class Response extends \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Response
{
    //########################################

    /**
     * @param array $params
     */
    public function processSuccess($params = [])
    {
        $data = [];

        $data = $this->appendQtyValues($data);
        $data = $this->appendLagTimeValues($data);
        $this->getListingProduct()->addData($data);

        $this->setLastSynchronizationDates();
        $this->getListingProduct()->save();
    }

    //########################################
}
