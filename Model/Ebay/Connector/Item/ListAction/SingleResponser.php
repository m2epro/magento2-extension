<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\ListAction;

use Ess\M2ePro\Model\Connector\Connection\Response\Message;

class SingleResponser extends \Ess\M2ePro\Model\Ebay\Connector\Item\Single\Responser
{
    //########################################

    protected function getSuccessfulMessage()
    {
        return 'Item was successfully Listed';
    }

    //########################################

    public function eventAfterExecuting()
    {
        $responseMessages = $this->getResponse()->getMessages()->getEntities();

        if (!$this->listingProduct->getAccount()->getChildObject()->isModeSandbox() &&
            $this->isEbayApplicationErrorAppeared($responseMessages)) {

            $this->markAsPotentialDuplicate();

            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromPreparedData(
                'An error occurred while Listing the Item. The Item has been blocked.
                 The next M2E Pro Synchronization will resolve the problem.',
                Message::TYPE_WARNING
            );

            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);
        }

        parent::eventAfterExecuting();
    }

    //########################################
}