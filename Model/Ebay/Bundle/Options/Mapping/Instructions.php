<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping;

class Instructions
{
    public const INSTRUCTION_TYPE = 'bundle_options_mapping_changed';
    private const INSTRUCTION_INITIATOR = 'bundle_options_mapping_change_handler';
    private const INSTRUCTION_PRIORITY = 100;

    private \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Instruction $instructionResource
    ) {
        $this->instructionResource = $instructionResource;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $products
     *
     * @throws \Ess\M2ePro\Model\Exception\Logic
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addForProducts(array $products)
    {
        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'listing_product_id' => $product->getId(),
                'type' => self::INSTRUCTION_TYPE,
                'initiator' => self::INSTRUCTION_INITIATOR,
                'priority' => self::INSTRUCTION_PRIORITY,
            ];
        }

        $this->instructionResource->add($data);
    }
}
