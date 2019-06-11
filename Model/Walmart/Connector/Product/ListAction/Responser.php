<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction\Response getResponseObject()
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Product\ListAction;

use Ess\M2ePro\Model\Connector\Connection\Response\Message;
use Ess\M2ePro\Model\Synchronization\Templates\Synchronization\Runner;
use Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator;

class Responser extends \Ess\M2ePro\Model\Walmart\Connector\Product\Responser
{
    // ########################################

    protected function processSuccess(array $params = array())
    {
        $this->getResponseObject()->processSuccess($params);
        $this->isSuccess = true;
    }

    protected function getSuccessfulMessage()
    {
        return null;
    }

    // ########################################

    protected function processResponseData()
    {
        $responseData = $this->getPreparedResponseData();

        if (empty($responseData['errors']) && empty($responseData['wpid'])) {

            $message = $this->getHelper('Module\Translation')->__(
                'The Item was not listed due to the unexpected error on Walmart side.
                 Please try to list this Item later.'
            );

            $messageData = array(
                Message::TYPE_KEY   => \Ess\M2ePro\Model\Response\Message::TYPE_ERROR,
                Message::TEXT_KEY   => $message,
                Message::SENDER_KEY => Message::SENDER_COMPONENT,
                Message::CODE_KEY   => '',
            );

            $messageObj = $this->modelFactory->getObject('Connector\Connection\Response\Message');
            $messageObj->initFromResponseData($messageData);

            if (!$this->processMessages([$messageObj])) {
                return;
            }
        }

        parent::processResponseData();
    }

    // ########################################

    protected function inspectProduct()
    {
        if (!$this->isSuccess) {
            return;
        }

        /** @var Runner $runner */
        $runner = $this->modelFactory->getObject('Synchronization\Templates\Synchronization\Runner');
        $runner->setConnectorModel('Walmart\Connector\Product\Dispatcher');
        $runner->setMaxProductsPerStep(100);

        /** @var Configurator $configurator */
        $configurator = $this->modelFactory->getObject('Walmart\Listing\Product\Action\Configurator');
        $configurator->reset();
        $configurator->allowQty();

        $this->listingProduct->setData('is_list_action', true);
        $this->listingProduct->setData('list_logs_action_id', $this->getLogsActionId());
        $this->listingProduct->setData('list_logs_action', $this->getLogsAction());

        $runner->addProduct(
            $this->listingProduct, \Ess\M2ePro\Model\Listing\Product::ACTION_RELIST, $configurator
        );

        $runner->execute();
    }

    // ########################################
}