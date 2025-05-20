<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Stop;

use Ess\M2ePro\Model\Amazon\Template\ChangeProcessor\ChangeProcessorAbstract as ChangeProcessor;

class Response extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Response
{
    /**
     * @ingeritdoc
     */
    public function processSuccess(array $params = []): void
    {
        $data = [];

        $data = $this->appendStatusChangerValue($data);
        $data = $this->appendQtyValues($data, null);

        $this->getListingProduct()->addData($data);
        $this->getAmazonListingProduct()->addData($data);

        $isStatusChangerUser = $this->getListingProduct()->getStatusChanger()
            === \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER;
        $isStoppedManually = $this->getListingProduct()->isInactive() && $isStatusChangerUser;
        $this->getAmazonListingProduct()->setIsStoppedManually($isStoppedManually);

        $this->setLastSynchronizationDates();
        $this->getListingProduct()->save();
    }

    protected function setLastSynchronizationDates()
    {
        $additionalData = $this->getListingProduct()->getAdditionalData();
        $additionalData['last_synchronization_dates']['qty'] = $this->getHelper('Data')->getCurrentGmtDate();
        $this->getListingProduct()->setSettings('additional_data', $additionalData);
    }

    public function throwRepeatActionInstructions()
    {
        $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getResource()->add(
            [
                [
                    'listing_product_id' => $this->getListingProduct()->getId(),
                    'type' => ChangeProcessor::INSTRUCTION_TYPE_QTY_DATA_CHANGED,
                    'initiator' => self::INSTRUCTION_INITIATOR,
                    'priority' => 80,
                ],
            ]
        );
    }
}
