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
    const INSTRUCTION_TYPE_CHECK_QTY        = 'success_relist_check_qty';
    const INSTRUCTION_TYPE_CHECK_LAG_TIME   = 'success_relist_check_lag_time';
    const INSTRUCTION_TYPE_CHECK_PRICE      = 'success_relist_check_price';
    const INSTRUCTION_TYPE_CHECK_PROMOTIONS = 'success_relist_check_promotions';
    const INSTRUCTION_TYPE_CHECK_DETAILS    = 'success_relist_check_details';

    //########################################

    /**
     * @param array $params
     */
    public function processSuccess($params = [])
    {
        $data = [];

        if ($this->getConfigurator()->isPriceAllowed()) {
            $data['is_online_price_invalid'] = 0;
        }

        $data = $this->appendStatusChangerValue($data);
        $data = $this->appendQtyValues($data);
        $data = $this->appendLagTimeValues($data);
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

    protected function processRecheckInstructions(array $data)
    {
        if (!isset($data['additional_data'])) {
            $data['additional_data'] = $this->getListingProduct()->getAdditionalData();
        }

        if (empty($data['additional_data']['recheck_properties'])) {
            return $data;
        }

        $instructionsData = [];

        foreach ($data['additional_data']['recheck_properties'] as $property) {
            $instructionType     = null;
            $instructionPriority = 0;

            switch ($property) {
                case 'qty':
                    $instructionType     = self::INSTRUCTION_TYPE_CHECK_QTY;
                    $instructionPriority = 80;
                    break;

                case 'lag_time':
                    $instructionType     = self::INSTRUCTION_TYPE_CHECK_LAG_TIME;
                    $instructionPriority = 60;
                    break;

                case 'price':
                    $instructionType     = self::INSTRUCTION_TYPE_CHECK_PRICE;
                    $instructionPriority = 60;
                    break;

                case 'promotions':
                    $instructionType     = self::INSTRUCTION_TYPE_CHECK_PROMOTIONS;
                    $instructionPriority = 30;
                    break;

                case 'details':
                    $instructionType     = self::INSTRUCTION_TYPE_CHECK_DETAILS;
                    $instructionPriority = 30;
                    break;
            }

            if ($instructionType === null) {
                continue;
            }

            $instructionsData[] = [
                'listing_product_id' => $this->getListingProduct()->getId(),
                'type'               => $instructionType,
                'initiator'          => self::INSTRUCTION_INITIATOR,
                'priority'           => $instructionPriority,
            ];
        }

        $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getResource()->add($instructionsData);

        unset($data['additional_data']['recheck_properties']);

        return $data;
    }

    //########################################
}
