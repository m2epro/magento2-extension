<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction;

class Response extends \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Response
{
    const INSTRUCTION_INITIATOR = 'list_action_response';

    const INSTRUCTION_TYPE_CHECK_QTY = 'success_list_check_qty';
    const INSTRUCTION_TYPE_CHECK_PRICE = 'success_list_check_price';
    const INSTRUCTION_TYPE_CHECK_PROMOTIONS = 'success_list_check_promotions';

    //########################################

    /**
     * @param array $params
     */
    public function processSuccess($params = array())
    {
        // list action include 2 steps (list details and relist with qty)
        $data = array(
            'status'     => \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED,
            'sku'        => $this->getRequestData()->getSku(),
            'wpid'       => $params['wpid'],
            'item_id'    => $params['item_id'],
            'gtin'       => $params['identifiers']['GTIN'],
            'online_qty' => 0,
            'list_date'  => $this->getHelper('Data')->getCurrentGmtDate()
        );

        $data = $this->appendStatusChangerValue($data);
        $data = $this->appendPriceValues($data);
        $data = $this->appendDetailsValues($data);
        $data = $this->appendProductIdsData($data);

        $this->getListingProduct()->addData($data);
        $this->getListingProduct()->getChildObject()->addData($data);

        $recheckDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $recheckDate->modify('+ 24 hours');

        $this->getListingProduct()->setSetting(
            'additional_data', 'recheck_after_list_date', $recheckDate->format('Y-m-d H:i:s')
        );
        $this->getListingProduct()->save();
    }

    //########################################
}