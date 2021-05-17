<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\SynchronizationTemplate\Checker;

use \Ess\M2ePro\Model\Listing\Product\Instruction\SynchronizationTemplate\Checker\AbstractModel as BaseAbstractModel;
use \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel as ChangeProcessorAbstract;
use \Ess\M2ePro\Model\Amazon\Template\Synchronization\ChangeProcessor as SynchronizationChangeProcessor;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\SynchronizationTemplate\Checker\AbstractModel
 */
abstract class AbstractModel extends BaseAbstractModel
{
    //########################################

    protected function getReviseInstructionTypes()
    {
        return array_unique(
            array_merge(
                $this->getReviseQtyInstructionTypes(),
                $this->getRevisePriceRegularInstructionTypes(),
                $this->getRevisePriceBusinessInstructionTypes(),
                $this->getReviseDetailsInstructionTypes(),
                $this->getReviseImagesInstructionTypes()
            )
        );
    }

    // ---------------------------------------

    protected function getReviseQtyInstructionTypes()
    {
        return [
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_QTY_DATA_POTENTIALLY_CHANGED,
            \Ess\M2ePro\Model\Amazon\Magento\Product\ChangeProcessor::INSTRUCTION_TYPE_QTY_DATA_CHANGED,
            \Ess\M2ePro\Model\Amazon\Template\ChangeProcessor\ChangeProcessorAbstract::INSTRUCTION_TYPE_QTY_DATA_CHANGED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_QTY_ENABLED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_QTY_DISABLED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_QTY_SETTINGS_CHANGED,
            \Ess\M2ePro\Model\Amazon\Listing\Product::INSTRUCTION_TYPE_CHANNEL_QTY_CHANGED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_QTY,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_STATUS_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_QTY_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    protected function getRevisePriceRegularInstructionTypes()
    {
        return [
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_PRICE_DATA_POTENTIALLY_CHANGED,
            \Ess\M2ePro\Model\Amazon\Template\ChangeProcessor\ChangeProcessorAbstract::INSTRUCTION_TYPE_PRICE_DATA_CHANGED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_PRICE_ENABLED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_PRICE_DISABLED,
            \Ess\M2ePro\Model\Amazon\Listing\Product::INSTRUCTION_TYPE_CHANNEL_REGULAR_PRICE_CHANGED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_PRICE_REGULAR,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRICE_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    protected function getRevisePriceBusinessInstructionTypes()
    {
        return [
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_PRICE_DATA_POTENTIALLY_CHANGED,
            \Ess\M2ePro\Model\Amazon\Template\ChangeProcessor\ChangeProcessorAbstract::INSTRUCTION_TYPE_PRICE_DATA_CHANGED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_PRICE_ENABLED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_PRICE_DISABLED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_PRICE_BUSINESS,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRICE_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    protected function getReviseDetailsInstructionTypes()
    {
        return [
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_DETAILS_ENABLED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_DETAILS_DISABLED,
            \Ess\M2ePro\Model\Amazon\Magento\Product\ChangeProcessor::INSTRUCTION_TYPE_DETAILS_DATA_CHANGED,
            \Ess\M2ePro\Model\Amazon\Template\ChangeProcessor\ChangeProcessorAbstract::INSTRUCTION_TYPE_DETAILS_DATA_CHANGED,
            \Ess\M2ePro\Model\Amazon\Listing\ChangeProcessor::INSTRUCTION_TYPE_CONDITION_DATA_CHANGED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_DETAILS,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    protected function getReviseImagesInstructionTypes()
    {
        return [
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_IMAGES_ENABLED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_IMAGES_DISABLED,
            \Ess\M2ePro\Model\Amazon\Magento\Product\ChangeProcessor::INSTRUCTION_TYPE_IMAGES_DATA_CHANGED,
            \Ess\M2ePro\Model\Amazon\Template\ChangeProcessor\ChangeProcessorAbstract::INSTRUCTION_TYPE_IMAGES_DATA_CHANGED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_IMAGES,
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

        if ($this->input->hasInstructionWithTypes($this->getRevisePriceRegularInstructionTypes())) {
            $propertiesData[] = 'price_regular';
        }

        if ($this->input->hasInstructionWithTypes($this->getRevisePriceBusinessInstructionTypes())) {
            $propertiesData[] = 'price_business';
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseDetailsInstructionTypes())) {
            $propertiesData[] = 'details';
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseImagesInstructionTypes())) {
            $propertiesData[] = 'images';
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

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $configurator */
        $configurator = $this->modelFactory->getObject('Amazon_Listing_Product_Action_Configurator');
        $configurator->setUnserializedData($additionalData['configurator']);

        $propertiesData = [];

        if ($configurator->isQtyAllowed()) {
            $propertiesData[] = 'qty';
        }

        if ($configurator->isRegularPriceAllowed()) {
            $propertiesData[] = 'price_regular';
        }

        if ($configurator->isBusinessPriceAllowed()) {
            $propertiesData[] = 'price_business';
        }

        if ($configurator->isDetailsAllowed()) {
            $propertiesData[] = 'details';
        }

        if ($configurator->isImagesAllowed()) {
            $propertiesData[] = 'images';
        }

        return $propertiesData;
    }

    //########################################
}
