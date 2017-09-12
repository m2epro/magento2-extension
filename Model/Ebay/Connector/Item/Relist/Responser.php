<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\Relist;

use Ess\M2ePro\Model\Connector\Connection\Response\Message;

/**
 * @method \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist\Response getResponseObject()
 */
class Responser extends \Ess\M2ePro\Model\Ebay\Connector\Item\Responser
{
    //########################################

    protected function getSuccessfulMessage()
    {
        return 'Item was successfully Relisted';
    }

    //########################################

    protected function processCompleted(array $data = array(), array $params = array())
    {
        if (!empty($data['already_active'])) {
            $this->getResponseObject()->processAlreadyActive($data, $params);

            // M2ePro\TRANSLATIONS
            // Item was already started on eBay
            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromPreparedData(
                'Item was already started on eBay',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_ERROR
            );

            $this->getLogger()->logListingProductMessage(
                $this->listingProduct, $message
            );

            return;
        }

        parent::processCompleted($data, $params);
    }

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

        if ($this->isConditionErrorAppeared($responseMessages)) {

            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromPreparedData(
                $this->getHelper('Module\Translation')->__(
                    'M2E Pro was not able to send Condition on eBay. Please try to perform the Relist Action once more.'
                ),
                Message::TYPE_WARNING
            );

            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);

            $additionalData = $this->listingProduct->getAdditionalData();
            $additionalData['is_need_relist_condition'] = true;

            $this->listingProduct
                ->setSettings('additional_data', $additionalData)
                ->save();
        }

        if ($this->getStatusChanger() == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_SYNCH &&
            $this->isItemCanNotBeAccessed($responseMessages)) {

            $itemId = null;
            if (isset($this->params['product']['request']['item_id'])) {
                $itemId = $this->params['product']['request']['item_id'];
            }

            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromPreparedData(
                $this->getHelper('Module\Translation')->__(
                    "This Item {$itemId} cannot be accessed on eBay, so the Relist action cannot be executed for it.
                    M2E Pro has automatically detected this issue and run the List action to solve it basing
                    on the List Rule of the Synchronization Policy."
                ),
                Message::TYPE_WARNING
            );

            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);

            $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
            $this->processAdditionalAction(
                \Ess\M2ePro\Model\Listing\Product::ACTION_LIST, $configurator,
                array('skip_check_the_same_product_already_listed_ids' => array($this->listingProduct->getId()))
            );
        }

        if ($this->getStatusChanger() == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_SYNCH &&
            !$this->getConfigurator()->isDefaultMode() &&
            $this->isNewRequiredSpecificNeeded($responseMessages)) {

            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromPreparedData(
                $this->getHelper('Module\Translation')->__(
                    'It has been detected that the Category you are using is going to require the Product Identifiers
                    to be specified (UPC, EAN, ISBN, etc.). The Relist Action will be automatically performed
                    to send the value(s) of the required Identifier(s) based on the settings
                    provided in eBay Catalog Identifiers section of the Description Policy.'
                ),
                Message::TYPE_WARNING
            );

            $this->getLogger()->logListingProductMessage($this->listingProduct, $message);

            $configurator = $this->modelFactory->getObject('Ebay\Listing\Product\Action\Configurator');
            $this->processAdditionalAction($this->getActionType(), $configurator);
        }

        $additionalData = $this->listingProduct->getAdditionalData();

        if ($this->isVariationErrorAppeared($responseMessages) &&
            $this->getRequestDataObject()->hasVariations() &&
            !isset($additionalData['is_variation_mpn_filled'])
        ) {
            $this->tryToResolveVariationMpnErrors();
        }

        if ($message = $this->isDuplicateErrorByUUIDAppeared($responseMessages)) {
            $this->processDuplicateByUUID($message);
        }

        if ($message = $this->isDuplicateErrorByEbayEngineAppeared($responseMessages)) {
            $this->processDuplicateByEbayEngine($message);
        }

        parent::eventAfterExecuting();
    }

    //########################################
}