<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Product\Relist;

/**
 * @method \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Relist\Response getResponseObject()
 */
class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Product\Responser
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

        $configurator = $this->modelFactory->getObject('Amazon_Listing_Product_Action_Configurator');
        $configurator->setUnserializedData($additionalData['skipped_action_configurator_data']);

        /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction\Manager $scheduledActionManager */
        $scheduledActionManager = $this->modelFactory->getObject('Listing_Product_ScheduledAction_Manager');
        // TODO fix here and on m1
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
