<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Stop;

use Ess\M2ePro\Model\Walmart\Template\ChangeProcessor\ChangeProcessorAbstract as ChangeProcessor;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Stop\Response
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

        $data = $this->appendStatusChangerValue($data);
        $data = $this->appendQtyValues($data);
        $data = $this->appendLagTimeValues($data);

        $this->getListingProduct()->addData($data);
        $this->getListingProduct()->getChildObject()->addData($data);

        $this->setLastSynchronizationDates();

        $this->getListingProduct()->save();
    }

    //########################################

    protected function setLastSynchronizationDates()
    {
        $additionalData = $this->getListingProduct()->getAdditionalData();
        $additionalData['last_synchronization_dates']['qty'] = $this->getHelper('Data')->getCurrentGmtDate();
        $this->getListingProduct()->setSettings('additional_data', $additionalData);
    }

    //########################################

    public function throwRepeatActionInstructions()
    {
        $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getResource()->add(
            [
                [
                    'listing_product_id' => $this->getListingProduct()->getId(),
                    'type'               => ChangeProcessor::INSTRUCTION_TYPE_QTY_DATA_CHANGED,
                    'initiator'          => self::INSTRUCTION_INITIATOR,
                    'priority'           => 80
                ]
            ]
        );
    }

    //########################################
}
