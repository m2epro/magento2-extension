<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping;

class ChangeHandler
{
    private \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\ChangeHandler\AffectedProducts $affectedProducts;
    /** @var \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\Instructions */
    private Instructions $instructions;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\ChangeHandler\AffectedProducts $affectedProducts,
        \Ess\M2ePro\Model\Ebay\Bundle\Options\Mapping\Instructions $instructions
    ) {
        $this->affectedProducts = $affectedProducts;
        $this->instructions = $instructions;
    }

    public function handle(array $optionTitles)
    {
        if (empty($optionTitles)) {
            return;
        }

        $affectedProducts = $this->affectedProducts->getListingProducts($optionTitles);
        $this->instructions->addForProducts($affectedProducts);
    }
}
