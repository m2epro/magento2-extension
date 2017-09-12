<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Item\Revise;

use Ess\M2ePro\Model\Connector\Connection\Response\Message;

/**
 * @method \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Revise\Response getResponseObject()
 */
class Responser extends \Ess\M2ePro\Model\Ebay\Connector\Item\Responser
{
    //########################################

    protected function getSuccessfulMessage()
    {
        return $this->getResponseObject()->getSuccessfulMessage();
    }

    //########################################

    protected function processCompleted(array $data = array(), array $params = array())
    {
        if (!empty($data['already_stop'])) {
            $this->getResponseObject()->processAlreadyStopped($data, $params);

            // M2ePro\TRANSLATIONS
            // Item was already Stopped on eBay
            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromPreparedData(
                'Item was already Stopped on eBay',
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

        if ($this->getStatusChanger() == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_SYNCH &&
            !$this->getConfigurator()->isDefaultMode() &&
            $this->isNewRequiredSpecificNeeded($responseMessages)) {

            $message = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $message->initFromPreparedData($this->getHelper('Module\Translation')->__(
                'It has been detected that the Category you are using is going to require the Product Identifiers
                to be specified (UPC, EAN, ISBN, etc.). Full Revise will be automatically performed to send
                the value(s) of the required Identifier(s) based on the settings
                provided in the eBay Catalog Identifiers section of the Description Policy.'),
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

        parent::eventAfterExecuting();
    }

    //########################################
}