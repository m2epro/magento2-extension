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
    // ########################################

    protected function getSuccessfulMessage()
    {
        if ($this->getLogsAction() == \Ess\M2ePro\Model\Listing\Log::ACTION_LIST_PRODUCT_ON_COMPONENT) {
            return 'Item was successfully Listed';
        }

        return 'Item was successfully Relisted';
    }

    // ########################################

    public function eventAfterExecuting()
    {
        parent::eventAfterExecuting();

        $additionalData = $this->listingProduct->getAdditionalData();
        if (!empty($additionalData['skipped_action_configurator_data'])) {
            $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
            $configurator->setData($additionalData['skipped_action_configurator_data']);

            unset($additionalData['skipped_action_configurator_data']);
        }

        if (!empty($additionalData['is_list_action'])) {
            unset($additionalData['is_list_action']);
        }

        $this->listingProduct->setSettings('additional_data', $additionalData)->save();
    }

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        $additionalData = $this->listingProduct->getAdditionalData();

        if (!empty($additionalData['is_list_action'])) {
            unset($additionalData['is_list_action']);
            $this->listingProduct->setSettings('additional_data', $additionalData)->save();
        }
    }

    // ########################################
}
