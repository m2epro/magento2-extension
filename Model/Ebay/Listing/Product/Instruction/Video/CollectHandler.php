<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Instruction\Video;

use Ess\M2ePro\Model\Magento\Product\ChangeProcessor\AbstractModel as ChangeProcessorAbstract;
use Ess\M2ePro\Model\Ebay\Template\ChangeProcessor\ChangeProcessorAbstract as ChangeProcessor;

class CollectHandler implements \Ess\M2ePro\Model\Listing\Product\Instruction\Handler\HandlerInterface
{
    private \Ess\M2ePro\Model\Ebay\Video\ProductProcessor $productProcessor;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Video\ProductProcessor $productProcessor
    ) {
        $this->productProcessor = $productProcessor;
    }

    private function getAffectedInstructionTypes(): array
    {
        return [
            ChangeProcessorAbstract::INSTRUCTION_TYPE_PRODUCT_DATA_POTENTIALLY_CHANGED,
            ChangeProcessor::INSTRUCTION_TYPE_VIDEO_DATA_CHANGED,
        ];
    }

    public function process(\Ess\M2ePro\Model\Listing\Product\Instruction\Handler\Input $input): void
    {
        if (!$this->shouldProcess($input)) {
            return;
        }

        $this->productProcessor->process($input->getListingProduct());
    }

    private function shouldProcess(\Ess\M2ePro\Model\Listing\Product\Instruction\Handler\Input $input): bool
    {
        return $input->hasInstructionWithTypes($this->getAffectedInstructionTypes())
            && $input->getListingProduct()->isListed();
    }
}
