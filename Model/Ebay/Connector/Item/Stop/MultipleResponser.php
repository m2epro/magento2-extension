<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\Stop;

class MultipleResponser extends \Ess\M2ePro\Model\Ebay\Connector\Item\Multiple\Responser
{
    //########################################

    protected function getSuccessfulMessage(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        return 'Item was successfully Stopped';
    }

    //########################################

    public function eventAfterExecuting()
    {
        parent::eventAfterExecuting();

        if (empty($this->params['params']['remove'])) {
            return;
        }

        foreach ($this->listingsProducts as $listingProduct) {
            $listingProduct->setData('status', \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED);
            $listingProduct->delete();
        }
    }

    //########################################

    protected function processCompleted(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                        array $data = array(), array $params = array())
    {
        if (!empty($data['already_stop'])) {

            $this->getResponseObject($listingProduct)->processSuccess($data, $params);

            // M2ePro\TRANSLATIONS
            // Item was already Stopped on eBay
            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromPreparedData(
                'Item was already Stopped on eBay',
                 \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $this->getLogger()->logListingProductMessage(
                $listingProduct, $message
            );

            return;
        }

        parent::processCompleted($listingProduct, $data, $params);
    }

    //########################################
}