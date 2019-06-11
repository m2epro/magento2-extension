<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Relist;

class Response extends \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Response
{
    const INSTRUCTION_INITIATOR = 'relist_action_response';

    const INSTRUCTION_TYPE_CHECK_QTY = 'success_relist_check_qty';
    const INSTRUCTION_TYPE_CHECK_PRICE = 'success_relist_check_price';
    const INSTRUCTION_TYPE_CHECK_PROMOTIONS = 'success_relist_check_promotions';
    const INSTRUCTION_TYPE_CHECK_DETAILS = 'success_relist_check_details';

    //########################################

    /**
     * @param array $params
     */
    public function processSuccess($params = array())
    {
        $data = array();

        if ($this->getConfigurator()->isDefaultMode()) {
            $data['synch_status'] = \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_OK;
            $data['synch_reasons'] = NULL;
        }

        if ($this->getConfigurator()->isPriceAllowed()) {
            $data['is_online_price_invalid'] = 0;
        }

        $data = $this->appendStatusChangerValue($data);
        $data = $this->appendQtyValues($data);
        $data = $this->appendPriceValues($data);
        $data = $this->appendPromotionsValues($data);

        $data = $this->processRecheckInstructions($data);

        if (isset($data['additional_data'])) {
            $data['additional_data'] = $this->getHelper('Data')->jsonEncode($data['additional_data']);
        }

        $this->getListingProduct()->addData($data);
        $this->getListingProduct()->getChildObject()->addData($data);

        $this->setLastSynchronizationDates();

        $this->getListingProduct()->save();
    }

    //########################################

    private function processRecheckInstructions(array $data)
    {
        if (!isset($data['additional_data'])) {
            $data['additional_data'] = $this->getListingProduct()->getAdditionalData();
        }

        if (empty($data['additional_data']['recheck_properties'])) {
            return $data;
        }

        $lpTable = $this->activeRecordFactory->getObject('Listing\Product')->getResource()->getMainTable();

        $synchReasons = array(
            \Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat::SYNCH_REASON_QTY,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat::SYNCH_REASON_PRICE,
            \Ess\M2ePro\Model\ResourceModel\Walmart\Template\SellingFormat::SYNCH_REASON_PROMOTIONS,
        );

        $this->resourceConnection->getConnection()->update(
            $lpTable,
            array(
                'synch_status' => \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_NEED,
                'synch_reasons' => new \Zend_Db_Expr(
                    "IF(synch_reasons IS NULL,
                        '".implode(',',$synchReasons)."',
                        CONCAT(synch_reasons,'".','.implode(',',$synchReasons)."')
                    )"
                )
            ),
            array('id = '.$this->getListingProduct()->getId())
            );

        unset($data['additional_data']['recheck_properties']);

        return $data;
    }

    //########################################
}