<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\Stop;

/**
 * Class \Ess\M2ePro\Model\Ebay\Connector\Item\Stop\Responser
 */
class Responser extends \Ess\M2ePro\Model\Ebay\Connector\Item\Responser
{
    //########################################

    protected function getSuccessfulMessage()
    {
        return 'Item was Stopped';
    }

    //########################################

    public function eventAfterExecuting()
    {
        parent::eventAfterExecuting();

        if (!empty($this->params['params']['remove'])) {
            /** @var \Ess\M2ePro\Model\Listing\Product\RemoveHandler $removeHandler */
            $removeHandler = $this->modelFactory->getObject('Listing_Product_RemoveHandler');
            $removeHandler->setListingProduct($this->listingProduct);
            $removeHandler->process();
        }
    }

    //########################################

    protected function processCompleted(array $data = [], array $params = [])
    {
        if (!empty($data['already_stop'])) {
            $this->getResponseObject()->processSuccess($data, $params);

            /** @var \Ess\M2ePro\Model\Connector\Connection\Response\Message $message */
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                'Item was already Stopped on eBay',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $this->getLogger()->logListingProductMessage(
                $this->listingProduct,
                $message
            );

            return;
        }

        parent::processCompleted($data, $params);
    }

    //########################################
}
