<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\PickupStore;

use Ess\M2ePro\Model\Ebay\Template\Synchronization\ChangeProcessor as SynchronizationChangeProcessor;
use Ess\M2ePro\Model\Listing\Product\Instruction\Handler\HandlerInterface as InstructionHandlerInterface;
use Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel as ChangeProcessorAbstract;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\PickupStore\Handler
 */
class Handler extends \Ess\M2ePro\Model\AbstractModel implements InstructionHandlerInterface
{
    //########################################

    protected function getAffectedInstructionTypes()
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
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_STATUS_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_QTY_CHANGED,
            \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel::
            INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Cron\Task\Listing\Product\InspectDirectChanges::INSTRUCTION_TYPE,
        ];
    }

    //########################################

    public function process(\Ess\M2ePro\Model\Listing\Product\Instruction\Handler\Input $input)
    {
        if (!$input->hasInstructionWithTypes($this->getAffectedInstructionTypes())) {
            return;
        }

        $listingProduct = $input->getListingProduct();

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();
        if (!$ebayListingProduct->getEbayAccount()->isPickupStoreEnabled()) {
            return;
        }

        $ebaySynchronizationTemplate = $ebayListingProduct->getEbaySynchronizationTemplate();
        if (!$ebaySynchronizationTemplate->isReviseUpdateQty()) {
            return;
        }

        $pickupStoreStateUpdater = $this->modelFactory->getObject('Ebay_Listing_Product_PickupStore_State_Updater');
        $pickupStoreStateUpdater->setListingProduct($listingProduct);

        if ($ebaySynchronizationTemplate->isReviseUpdateQtyMaxAppliedValueModeOn()) {
            $pickupStoreStateUpdater->setMaxAppliedQtyValue(
                $ebaySynchronizationTemplate->getReviseUpdateQtyMaxAppliedValue()
            );
        }

        $pickupStoreStateUpdater->process();
    }

    //########################################
}
