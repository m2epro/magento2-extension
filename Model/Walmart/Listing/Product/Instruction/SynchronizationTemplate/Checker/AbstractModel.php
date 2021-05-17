<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Instruction\SynchronizationTemplate\Checker;

use \Ess\M2ePro\Model\Listing\Product\Instruction\SynchronizationTemplate\Checker\AbstractModel as BaseAbstractModel;
use \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel as ChangeProcessorAbstract;
use Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\ListAction\Response as ListActionResponse;
use \Ess\M2ePro\Model\Walmart\Template\Synchronization\ChangeProcessor as SynchronizationChangeProcessor;
use \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator as ActionConfigurator;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Instruction\SynchronizationTemplate\Checker\AbstractModel
 */
abstract class AbstractModel extends BaseAbstractModel
{
    //########################################

    protected function getReviseInstructionTypes()
    {
        return array_unique(
            array_merge(
                $this->getReviseQtyInstructionTypes(),
                $this->getReviseLagTimeInstructionTypes(),
                $this->getRevisePriceInstructionTypes(),
                $this->getRevisePromotionsInstructionTypes(),
                $this->getReviseDetailsInstructionTypes()
            )
        );
    }

    // ---------------------------------------

    protected function getReviseQtyInstructionTypes()
    {
        return [
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_QTY_DATA_POTENTIALLY_CHANGED,
            \Ess\M2ePro\Model\Walmart\Template\ChangeProcessor\ChangeProcessorAbstract::INSTRUCTION_TYPE_QTY_DATA_CHANGED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_QTY_ENABLED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_QTY_DISABLED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_QTY_SETTINGS_CHANGED,
            \Ess\M2ePro\Model\Walmart\Listing\Product::INSTRUCTION_TYPE_CHANNEL_QTY_CHANGED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_QTY,
            ListActionResponse::INSTRUCTION_TYPE_CHECK_QTY,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_STATUS_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_QTY_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    protected function getReviseLagTimeInstructionTypes()
    {
        return [
            \Ess\M2ePro\Model\Walmart\Magento\Product\ChangeProcessor::INSTRUCTION_TYPE_LAG_TIME_DATA_CHANGED,
            \Ess\M2ePro\Model\Walmart\Template\ChangeProcessor\ChangeProcessorAbstract::INSTRUCTION_TYPE_LAG_TIME_DATA_CHANGED,

            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_LAG_TIME,
            ListActionResponse::INSTRUCTION_TYPE_CHECK_LAG_TIME,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    protected function getRevisePriceInstructionTypes()
    {
        return [
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_PRICE_DATA_POTENTIALLY_CHANGED,
            \Ess\M2ePro\Model\Walmart\Template\ChangeProcessor\ChangeProcessorAbstract::INSTRUCTION_TYPE_PRICE_DATA_CHANGED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_PRICE_ENABLED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_PRICE_DISABLED,
            \Ess\M2ePro\Model\Walmart\Listing\Product::INSTRUCTION_TYPE_CHANNEL_PRICE_CHANGED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_PRICE,
            ListActionResponse::INSTRUCTION_TYPE_CHECK_PRICE,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRICE_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    protected function getRevisePromotionsInstructionTypes()
    {
        return [
            \Ess\M2ePro\Model\Walmart\Magento\Product\ChangeProcessor::INSTRUCTION_TYPE_PROMOTIONS_DATA_CHANGED,
            \Ess\M2ePro\Model\Walmart\Template\ChangeProcessor\ChangeProcessorAbstract::INSTRUCTION_TYPE_PROMOTIONS_DATA_CHANGED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_PROMOTIONS_ENABLED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_PROMOTIONS_DISABLED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_PROMOTIONS,
            ListActionResponse::INSTRUCTION_TYPE_CHECK_PROMOTIONS,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    protected function getReviseDetailsInstructionTypes()
    {
        return [
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_DETAILS_ENABLED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_DETAILS_DISABLED,
            \Ess\M2ePro\Model\Walmart\Magento\Product\ChangeProcessor::INSTRUCTION_TYPE_DETAILS_DATA_CHANGED,
            \Ess\M2ePro\Model\Walmart\Template\ChangeProcessor\ChangeProcessorAbstract::INSTRUCTION_TYPE_DETAILS_DATA_CHANGED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_DETAILS,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    //########################################

    protected function getPropertiesDataFromInputInstructions()
    {
        if (!$this->input->hasInstructionWithTypes($this->getReviseInstructionTypes())) {
            return [];
        }

        $propertiesData = [];

        if ($this->input->hasInstructionWithTypes($this->getReviseQtyInstructionTypes())) {
            $propertiesData[] = 'qty';
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseLagTimeInstructionTypes())) {
            $propertiesData[] = 'lag_time';
        }

        if ($this->input->hasInstructionWithTypes($this->getRevisePriceInstructionTypes())) {
            $propertiesData[] = 'price';
        }

        if ($this->input->hasInstructionWithTypes($this->getRevisePromotionsInstructionTypes())) {
            $propertiesData[] = 'promotions';
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseDetailsInstructionTypes())) {
            $propertiesData[] = 'details';
        }

        return $propertiesData;
    }

    protected function getPropertiesDataFromInputScheduledAction()
    {
        if (!$this->input->getScheduledAction() || !$this->input->getScheduledAction()->isActionTypeRevise()) {
            return [];
        }

        $additionalData = $this->input->getScheduledAction()->getAdditionalData();
        if (empty($additionalData['configurator'])) {
            return [];
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $configurator */
        $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
        $configurator->setUnserializedData($additionalData['configurator']);

        $propertiesData = [];

        if ($configurator->isQtyAllowed()) {
            $propertiesData[] = 'qty';
        }

        if ($configurator->isLagTimeAllowed()) {
            $propertiesData[] = 'lag_time';
        }

        if ($configurator->isPriceAllowed()) {
            $propertiesData[] = 'price';
        }

        if ($configurator->isPromotionsAllowed()) {
            $propertiesData[] = 'promotions';
        }

        if ($configurator->isDetailsAllowed()) {
            $propertiesData[] = 'details';
        }

        return $propertiesData;
    }

    //########################################

    protected function checkUpdatePriceOrPromotionsFeedsLock(ActionConfigurator $configurator, array &$tags, $action)
    {
        if (count($configurator->getAllowedDataTypes()) !== 1) {
            return;
        }

        if (!$configurator->isPriceAllowed() && !$configurator->isPromotionsAllowed()) {
            return;
        }

        if (!$this->isLockedForUpdatePriceOrPromotions()) {
            return;
        }

        if ($configurator->isPriceAllowed()) {
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                'Item Price cannot yet be submitted. Walmart allows updating the Price information no sooner than
                24 hours after the relevant product is listed on their website.',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
            );

            $configurator->disallowPrice();
            unset($tags['price']);
        } else {
            $message = $this->modelFactory->getObject('Connector_Connection_Response_Message');
            $message->initFromPreparedData(
                'Item Promotion Price cannot yet be submitted. Walmart allows updating the Promotion Price
                information no sooner than 24 hours after the relevant product is listed on their website.',
                \Ess\M2ePro\Model\Connector\Connection\Response\Message::TYPE_WARNING
            );

            $configurator->disallowPromotions();
            unset($tags['promotions']);
        }

        $logger = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Logger');
        $logger->setAction($action);
        $logger->setActionId($this->activeRecordFactory->getObject('Listing_Log')->getResource()->getNextActionId());
        $logger->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION);
        $logger->logListingProductMessage($this->input->getListingProduct(), $message);
    }

    protected function isLockedForUpdatePriceOrPromotions()
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $this->input->getListingProduct()->getChildObject();

        if ($walmartListingProduct->getListDate() === null) {
            return false;
        }

        try {
            $borderDate = new \DateTime($walmartListingProduct->getListDate(), new \DateTimeZone('UTC'));
            $borderDate->modify('+24 hours');
        } catch (\Exception $exception) {
            return false;
        }

        if ($borderDate < new \DateTime('now', new \DateTimeZone('UTC'))) {
            return false;
        }

        return true;
    }

    //########################################
}
