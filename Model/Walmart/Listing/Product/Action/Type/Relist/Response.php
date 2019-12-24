<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Relist;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Relist\Response
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

        if ($this->getConfigurator()->isDefaultMode()) {
            $data['synch_status'] = \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_OK;
            $data['synch_reasons'] = null;
        }

        if ($this->getConfigurator()->isPriceAllowed()) {
            $data['is_online_price_invalid'] = 0;
        }

        $data = $this->appendStatusChangerValue($data);
        $data = $this->appendQtyValues($data);
        $data = $this->appendLagTimeValues($data);
        $data = $this->appendPriceValues($data);
        $data = $this->appendPromotionsValues($data);

        if (isset($data['additional_data'])) {
            $data['additional_data'] = $this->getHelper('Data')->jsonEncode($data['additional_data']);
        }

        $this->getListingProduct()->addData($data);
        $this->getListingProduct()->getChildObject()->addData($data);

        $this->setLastSynchronizationDates();

        $this->getListingProduct()->save();
    }

    //########################################
}
