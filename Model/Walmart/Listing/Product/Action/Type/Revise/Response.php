<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Revise;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Revise\Response
 */
class Response extends \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Response
{
    //########################################

    /**
     * @ingeritdoc
     */
    public function processSuccess(array $params = []): void
    {
        $data = [];

        if ($this->getRequestData()->getIsNeedProductIdUpdate()) {
            $data['wpid'] = $params['wpid'];
        }

        if ($this->getConfigurator()->isPriceAllowed()) {
            $data['is_online_price_invalid'] = 0;
        }

        $data = $this->appendStatusChangerValue($data);
        $data = $this->appendQtyValues($data);
        $data = $this->appendLagTimeValues($data);
        $data = $this->appendPriceValues($data);
        $data = $this->appendPromotionsValues($data);
        $data = $this->appendDetailsValues($data);
        $data = $this->appendStartDate($data);
        $data = $this->appendEndDate($data);
        $data = $this->appendChangedSku($data);
        $data = $this->appendProductIdsData($data);

        $this->getListingProduct()->addData($data);
        $this->getWalmartListingProduct()->addData($data);
        $this->getWalmartListingProduct()->setIsStoppedManually(false);

        $this->setLastSynchronizationDates();

        $this->getListingProduct()->save();
    }

    //########################################
}
