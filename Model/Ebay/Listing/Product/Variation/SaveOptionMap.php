<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Variation;

class SaveOptionMap
{
    public const INSTRUCTION_INITIATOR = 'save_option_mapping';
    public const INSTRUCTION_TYPE = 'option_mapping_changed';

    private Updater $variationUpdater;
    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource;

    public function __construct(
        Updater $variationUpdater,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource
    ) {
        $this->variationUpdater = $variationUpdater;
        $this->instructionResource = $instructionResource;
    }

    /**
     * @param array $listingProducts
     * @param array $replacedOptions
     *
     * @throws \Exception
     */
    public function execute(array $listingProducts, array $replacedOptions): void
    {
        $instructions = [];
        foreach ($listingProducts as $listingProduct) {
            $this->saveReplacedVariationOptions($listingProduct, $replacedOptions);
            $this->variationUpdater->process($listingProduct);

            $instructions[] = [
                'listing_product_id' => $listingProduct->getId(),
                'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
                'type' => self::INSTRUCTION_TYPE,
                'initiator' => self::INSTRUCTION_INITIATOR,
                'priority' => 100,
            ];
        }

        $this->instructionResource->add($instructions);
    }

    private function saveReplacedVariationOptions(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        array $replacedOptions
    ) {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Product $ebayListingProduct */
        $ebayListingProduct = $listingProduct->getChildObject();
        $ebayListingProduct->setReplacedVariationOptions($replacedOptions);
        $listingProduct->save();
    }
}
