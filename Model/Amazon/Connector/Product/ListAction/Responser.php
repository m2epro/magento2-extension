<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\ListAction;

/**
 * @method \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\ListAction\Response getResponseObject()
 */
class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Product\Responser
{
    //########################################

    protected function getSuccessfulMessage()
    {
        return 'Item was successfully Listed';
    }

    //########################################

    protected function processSuccess(array $params = [])
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $this->listingProduct->getChildObject();

        if ($amazonListingProduct->getVariationManager()->isRelationMode() &&
            !$this->getRequestDataObject()->hasProductId() &&
            empty($params['general_id'])
        ) {
            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                'Unexpected error. The ASIN/ISBN for Parent or Child Product was not returned from Amazon.
                 Operation cannot be finished correctly.',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $this->getLogger()->logListingProductMessage(
                $this->listingProduct,
                $message,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
            );

            return;
        }

        parent::processSuccess($params);
    }

    protected function getSuccessfulParams()
    {
        $responseData = $this->getPreparedResponseData();

        if (empty($responseData['asins'])) {
            return [];
        }

        return ['general_id' => $responseData['asins']];
    }

    //########################################
}
