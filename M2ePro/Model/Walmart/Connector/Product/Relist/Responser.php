<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Relist\Response getResponseObject()
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Product\Relist;

/**
 * Class \Ess\M2ePro\Model\Walmart\Connector\Product\Relist\Responser
 */
class Responser extends \Ess\M2ePro\Model\Walmart\Connector\Product\Responser
{
    //########################################

    protected function getSuccessfulMessage()
    {
        return 'Item was successfully Relisted';
    }

    //########################################

    public function eventAfterExecuting()
    {
        parent::eventAfterExecuting();

        $additionalData = $this->listingProduct->getAdditionalData();
        if (empty($additionalData['skipped_action_configurator_data'])) {
            return;
        }

        $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
        $configurator->setUnserializedData($additionalData['skipped_action_configurator_data']);

        $scheduledActionManager = $this->modelFactory->getObject('Listing_Product_ScheduledAction_Manager');
        $scheduledActionManager->addReviseAction(
            $this->listingProduct,
            $configurator,
            false,
            $this->params['params']
        );

        unset($additionalData['skipped_action_configurator_data']);
        $this->listingProduct->setSettings('additional_data', $additionalData)->save();
    }

    //########################################
}
