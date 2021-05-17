<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\Repricing;

use \Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel as ChangeProcessorAbstract;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\Repricing\Handler
 */
class Handler extends \Ess\M2ePro\Model\AbstractModel implements
    \Ess\M2ePro\Model\Listing\Product\Instruction\Handler\HandlerInterface
{
    //########################################

    protected function getAffectedInstructionTypes()
    {
        return [
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_PRICE_DATA_POTENTIALLY_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_STATUS_DATA_POTENTIALLY_CHANGED,
            ChangeProcessorAbstract::INSTRUCTION_TYPE_MAGMI_PLUGIN_PRODUCT_CHANGED,
            \Ess\M2ePro\Model\Amazon\Magento\Product\ChangeProcessor::INSTRUCTION_TYPE_REPRICING_DATA_CHANGED,
            \Ess\M2ePro\Model\Amazon\Account\Repricing\ChangeProcessor::INSTRUCTION_TYPE_ACCOUNT_REPRICING_DATA_CHANGED,
            \Ess\M2ePro\Model\Amazon\Repricing\Synchronization\General::INSTRUCTION_TYPE_STATUS_CHANGED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_INITIATOR_MOVING_PRODUCT_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_REMAP_FROM_LISTING,
            \Ess\M2ePro\Model\Amazon\Template\ChangeProcessor\ChangeProcessorAbstract::INSTRUCTION_TYPE_PRICE_DATA_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRODUCT_CHANGED,
            \Ess\M2ePro\PublicServices\Product\SqlChange::INSTRUCTION_TYPE_PRICE_CHANGED,
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

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();

        if (!$amazonListingProduct->isRepricingUsed()) {
            return;
        }

        if ($this->isProcessRequired($amazonListingProduct->getRepricing())) {
            $amazonListingProduct->getRepricing()->setData('is_process_required', true);
            $amazonListingProduct->getRepricing()->save();
            return;
        }

        if ($amazonListingProduct->getRepricing()->isProcessRequired()) {
            $amazonListingProduct->getRepricing()->setData('is_process_required', false);
            $amazonListingProduct->getRepricing()->save();
        }
    }

    //########################################

    protected function isProcessRequired(\Ess\M2ePro\Model\Amazon\Listing\Product\Repricing $listingProductRepricing)
    {
        $isDisabled = $listingProductRepricing->isDisabled();
        $isRepricingManaged = $listingProductRepricing->isOnlineManaged();

        if ($isDisabled && !$isRepricingManaged) {
            return false;
        }

        if ($isDisabled == $listingProductRepricing->getLastUpdatedIsDisabled() &&
            $listingProductRepricing->getRegularPrice() == $listingProductRepricing->getLastUpdatedRegularPrice() &&
            $listingProductRepricing->getMinPrice() == $listingProductRepricing->getLastUpdatedMinPrice() &&
            $listingProductRepricing->getMaxPrice() == $listingProductRepricing->getLastUpdatedMaxPrice()
        ) {
            return false;
        }

        return true;
    }

    //########################################
}
