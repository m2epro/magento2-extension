<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\ComplianceDocuments;

use Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract as ChangeProcessor;
use Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel as ChangeProcessorAbstract;

class Handler implements \Ess\M2ePro\Model\Listing\Product\Instruction\Handler\HandlerInterface
{
    private \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductProcessor $productProcessor;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ProductProcessor $productProcessor
    ) {
        $this->productProcessor = $productProcessor;
    }

    public function process(\Ess\M2ePro\Model\Listing\Product\Instruction\Handler\Input $input)
    {
        if (!$this->shouldProcess($input)) {
            return;
        }

        $this->productProcessor->process($input->getListingProduct(), true);
    }

    private function shouldProcess(\Ess\M2ePro\Model\Listing\Product\Instruction\Handler\Input $input): bool
    {
        if (!$input->getListingProduct()->isListed()) {
            return false;
        }

        return $input->hasInstructionWithTypes($this->getAffectedInstructionTypes());
    }

    private function getAffectedInstructionTypes(): array
    {
        return [
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_DATA_POTENTIALLY_CHANGED,
            ChangeProcessor::INSTRUCTION_TYPE_COMPLIANCE_DOCUMENTS_DATA_CHANGED,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_LISTING,
            \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_CHANGE_LISTING_STORE_VIEW,
        ];
    }
}
