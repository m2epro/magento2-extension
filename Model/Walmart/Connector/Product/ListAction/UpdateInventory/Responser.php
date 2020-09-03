<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Product\ListAction\UpdateInventory;

/**
 * Class \Ess\M2ePro\Model\Walmart\Connector\Product\ListAction\UpdateInventory\Responser
 */
class Responser extends \Ess\M2ePro\Model\Walmart\Connector\Product\Responser
{
    /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\ProcessingList */
    protected $processingList;

    //########################################

    protected function getSuccessfulMessage()
    {
        return null;
    }

    //########################################

    protected function getResponseObject()
    {
        $responseObject = parent::getResponseObject();
        $responseObject->setRequestMetaData([]);

        return $responseObject;
    }

    //########################################

    protected function getOrmActionType()
    {
        switch ($this->getActionType()) {
            case \Ess\M2ePro\Model\Listing\Product::ACTION_LIST:
                return 'ListAction_UpdateInventory';
        }

        throw new \Ess\M2ePro\Model\Exception('Wrong Action type');
    }

    // ---------------------------------------

    protected function getRequestDataObject()
    {
        $requestObject = parent::getRequestDataObject();
        $requestObject->setData($this->processingList->getRelistRequestData());

        return $requestObject;
    }

    protected function getConfigurator()
    {
        $configurator = parent::getConfigurator();
        $configurator->setUnserializedData($this->processingList->getRelistConfiguratorData());

        return $configurator;
    }

    //########################################

    public function setProcessingList(\Ess\M2ePro\Model\Walmart\Listing\Product\Action\ProcessingList $processingList)
    {
        $this->processingList = $processingList;
        return $this;
    }

    //########################################
}
