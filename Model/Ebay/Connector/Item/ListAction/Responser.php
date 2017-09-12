<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\ListAction;

use Ess\M2ePro\Model\Connector\Connection\Response\Message;

class Responser extends \Ess\M2ePro\Model\Ebay\Connector\Item\Responser
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

        if ($message = $this->isDuplicateErrorByUUIDAppeared($responseMessages)) {
            $this->processDuplicateByUUID($message);
        }

        if ($message = $this->isDuplicateErrorByEbayEngineAppeared($responseMessages)) {
            $this->processDuplicateByEbayEngine($message);
        }

        parent::eventAfterExecuting();
    }

    protected function inspectProduct()
    {
        if ($this->isSuccess) {
            parent::inspectProduct();
            return;
        }

        /**
         * Flag 'need_synch_rules_check' can be set by Reslit synch,
         * when List action from Stop status (can be initiated only manually) in progress.
         * If original List action was skipped or performed with error, we need initiate it again with new data.
         */
        if (!$this->listingProduct->needSynchRulesCheck()) {
            return;
        }

        $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');

        $responseData = $this->getPreparedResponseData();
        if (empty($responseData['request_time']) && !empty($responseData['start_processing_date'])) {
            $configurator->setParams(array('start_processing_date' => $responseData['start_processing_date']));
        }

        $this->processAdditionalAction(
            \Ess\M2ePro\Model\Listing\Product::ACTION_LIST, $configurator,
            array(
                'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_SYNCH,
                'skip_check_the_same_product_already_listed_ids' => array($this->listingProduct->getId())
            )
        );
    }

    //########################################
}