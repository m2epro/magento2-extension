<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\SynchronizationTemplate\Checker;

use \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel as ChangeProcessorAbstract;
use \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor as SynchronizationChangeProcessor;
use \Ess\M2ePro\Model\Listing\Product\Instruction\SynchronizationTemplate\Checker\AbstractModel as CheckerAbstractModel;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\SynchronizationTemplate\Checker\AbstractModel
 */
abstract class AbstractModel extends CheckerAbstractModel
{
    //########################################

    protected function getReviseInstructionTypes()
    {
        return array_unique(
            array_merge(
                $this->getReviseQtyInstructionTypes(),
                $this->getRevisePriceInstructionTypes(),
                $this->getReviseTitleInstructionTypes(),
                $this->getReviseSubtitleInstructionTypes(),
                $this->getReviseDescriptionInstructionTypes(),
                $this->getReviseImagesInstructionTypes(),
                $this->getReviseCategoriesInstructionTypes(),
                $this->getRevisePartsInstructionTypes(),
                $this->getReviseShippingInstructionTypes(),
                $this->getRevisePaymentInstructionTypes(),
                $this->getReviseReturnInstructionTypes(),
                $this->getReviseOtherInstructionTypes()
            )
        );
    }

    // ---------------------------------------

    protected function getReviseQtyInstructionTypes()
    {
        return [
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_QTY_DATA_POTENTIALLY_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract::INSTRUCTION_TYPE_QTY_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_QTY_ENABLED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_QTY_DISABLED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_QTY_SETTINGS_CHANGED,
            \Ess\M2ePro\Model\Ebay\Listing\Product::INSTRUCTION_TYPE_CHANNEL_QTY_CHANGED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_QTY,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_STATUS_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_QTY_CHANGED,
            \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel::
            INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    protected function getRevisePriceInstructionTypes()
    {
        return [
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_PRICE_DATA_POTENTIALLY_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract::
            INSTRUCTION_TYPE_PRICE_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_PRICE_ENABLED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_PRICE_DISABLED,
            \Ess\M2ePro\Model\Ebay\Listing\Product::INSTRUCTION_TYPE_CHANNEL_PRICE_CHANGED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_PRICE,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRICE_CHANGED,
            \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel::
            INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    protected function getReviseTitleInstructionTypes()
    {
        return [
            \Ess\M2ePro\Model\Ebay\Magento\Product\ChangeProcessor::INSTRUCTION_TYPE_TITLE_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract::
            INSTRUCTION_TYPE_TITLE_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_TITLE_ENABLED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_TITLE_DISABLED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_TITLE,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel::
            INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    protected function getReviseSubtitleInstructionTypes()
    {
        return [
            \Ess\M2ePro\Model\Ebay\Magento\Product\ChangeProcessor::INSTRUCTION_TYPE_SUBTITLE_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract::
            INSTRUCTION_TYPE_SUBTITLE_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_SUBTITLE_ENABLED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_SUBTITLE_DISABLED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_SUBTITLE,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel::
            INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    protected function getReviseDescriptionInstructionTypes()
    {
        return [
            \Ess\M2ePro\Model\Ebay\Magento\Product\ChangeProcessor::INSTRUCTION_TYPE_DESCRIPTION_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract::
            INSTRUCTION_TYPE_DESCRIPTION_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::
            INSTRUCTION_TYPE_REVISE_DESCRIPTION_ENABLED,
            SynchronizationChangeProcessor::INSTRUCTION_TYPE_REVISE_DESCRIPTION_DISABLED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_DESCRIPTION,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel::
            INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
            \Ess\M2ePro\Model\Ebay\Template\Description::INSTRUCTION_TYPE_MAGENTO_STATIC_BLOCK_IN_DESCRIPTION_CHANGED
        ];
    }

    protected function getReviseImagesInstructionTypes()
    {
        return [
            \Ess\M2ePro\Model\Ebay\Magento\Product\ChangeProcessor::INSTRUCTION_TYPE_IMAGES_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract::
            INSTRUCTION_TYPE_IMAGES_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_IMAGES_ENABLED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_IMAGES_DISABLED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_IMAGES,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel::
            INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
            \Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract::
            INSTRUCTION_TYPE_VARIATION_IMAGES_DATA_CHANGED,
        ];
    }

    protected function getReviseVariationImagesInstructionTypes()
    {
        return [
            \Ess\M2ePro\Model\Ebay\Magento\Product\ChangeProcessor::INSTRUCTION_TYPE_IMAGES_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract::
            INSTRUCTION_TYPE_IMAGES_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_IMAGES_ENABLED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_IMAGES_DISABLED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_IMAGES,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel::
            INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
            \Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract::
            INSTRUCTION_TYPE_VARIATION_IMAGES_DATA_CHANGED,
        ];
    }

    protected function getReviseCategoriesInstructionTypes()
    {
        return [
            \Ess\M2ePro\Model\Ebay\Magento\Product\ChangeProcessor::INSTRUCTION_TYPE_CATEGORIES_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract::
            INSTRUCTION_TYPE_CATEGORIES_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_CATEGORIES_ENABLED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::
            INSTRUCTION_TYPE_REVISE_CATEGORIES_DISABLED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_CATEGORIES,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel::
            INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    protected function getRevisePartsInstructionTypes()
    {
        return [
            \Ess\M2ePro\Model\Ebay\Magento\Product\ChangeProcessor::INSTRUCTION_TYPE_PARTS_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract::
            INSTRUCTION_TYPE_PARTS_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::
            INSTRUCTION_TYPE_REVISE_PARTS_ENABLED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::
            INSTRUCTION_TYPE_REVISE_PARTS_DISABLED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist\Response::
            INSTRUCTION_TYPE_CHECK_PARTS,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel::
            INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    protected function getReviseShippingInstructionTypes()
    {
        return [
            \Ess\M2ePro\Model\Ebay\Magento\Product\ChangeProcessor::INSTRUCTION_TYPE_SHIPPING_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract::
            INSTRUCTION_TYPE_SHIPPING_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_SHIPPING_ENABLED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_SHIPPING_DISABLED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_SHIPPING,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel::
            INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    protected function getRevisePaymentInstructionTypes()
    {
        return [
            \Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract::
            INSTRUCTION_TYPE_PAYMENT_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_PAYMENT_ENABLED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_PAYMENT_DISABLED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_PAYMENT,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel::
            INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    protected function getReviseReturnInstructionTypes()
    {
        return [
            \Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract::
            INSTRUCTION_TYPE_RETURN_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_RETURN_ENABLED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_RETURN_DISABLED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_RETURN,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel::
            INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    protected function getReviseOtherInstructionTypes()
    {
        return [
            \Ess\M2ePro\Model\Ebay\Magento\Product\ChangeProcessor::INSTRUCTION_TYPE_OTHER_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract::
            INSTRUCTION_TYPE_OTHER_DATA_CHANGED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_OTHER_ENABLED,
            \Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor::INSTRUCTION_TYPE_REVISE_OTHER_DISABLED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Relist\Response::INSTRUCTION_TYPE_CHECK_OTHER,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel::
            INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
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

        if ($this->input->hasInstructionWithTypes($this->getRevisePriceInstructionTypes())) {
            $propertiesData[] = 'price';
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseTitleInstructionTypes())) {
            $propertiesData[] = 'title';
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseSubtitleInstructionTypes())) {
            $propertiesData[] = 'subtitle';
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseDescriptionInstructionTypes())) {
            $propertiesData[] = 'description';
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseImagesInstructionTypes())) {
            $propertiesData[] = 'images';
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseCategoriesInstructionTypes())) {
            $propertiesData[] = 'categories';
        }

        if ($this->input->hasInstructionWithTypes($this->getRevisePartsInstructionTypes())) {
            $propertiesData[] = 'parts';
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseShippingInstructionTypes())) {
            $propertiesData[] = 'shipping';
        }

        if ($this->input->hasInstructionWithTypes($this->getRevisePaymentInstructionTypes())) {
            $propertiesData[] = 'payment';
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseReturnInstructionTypes())) {
            $propertiesData[] = 'return';
        }

        if ($this->input->hasInstructionWithTypes($this->getReviseOtherInstructionTypes())) {
            $propertiesData[] = 'other';
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

        $configurator = $this->modelFactory->getObject('Ebay_Listing_Product_Action_Configurator');
        $configurator->setUnserializedData($additionalData['configurator']);

        $propertiesData = [];

        if ($configurator->isQtyAllowed()) {
            $propertiesData[] = 'qty';
        }

        if ($configurator->isPriceAllowed()) {
            $propertiesData[] = 'price';
        }

        if ($configurator->isTitleAllowed()) {
            $propertiesData[] = 'title';
        }

        if ($configurator->isSubtitleAllowed()) {
            $propertiesData[] = 'subtitle';
        }

        if ($configurator->isDescriptionAllowed()) {
            $propertiesData[] = 'description';
        }

        if ($configurator->isImagesAllowed()) {
            $propertiesData[] = 'images';
        }

        if ($configurator->isCategoriesAllowed()) {
            $propertiesData[] = 'categories';
        }

        if ($configurator->isPaymentAllowed()) {
            $propertiesData[] = 'payment';
        }

        if ($configurator->isShippingAllowed()) {
            $propertiesData[] = 'shipping';
        }

        if ($configurator->isReturnAllowed()) {
            $propertiesData[] = 'return';
        }

        if ($configurator->isOtherAllowed()) {
            $propertiesData[] = 'other';
        }

        return $propertiesData;
    }

    //########################################
}
