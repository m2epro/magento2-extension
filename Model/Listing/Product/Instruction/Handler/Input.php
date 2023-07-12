<?php

namespace Ess\M2ePro\Model\Listing\Product\Instruction\Handler;

use Ess\M2ePro\Model\Listing\Product;

class Input extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var Product */
    protected $listingProduct;
    /** @var \Ess\M2ePro\Model\Listing\Product\Instruction[] */
    protected $instructions = [];

    public function setListingProduct(Product $listingProduct): self
    {
        $this->listingProduct = $listingProduct;

        return $this;
    }

    public function getListingProduct(): Product
    {
        return $this->listingProduct;
    }

    /**
     * @param \Ess\M2ePro\Model\Listing\Product\Instruction[] $instructions
     *
     * @return $this
     */
    public function setInstructions(array $instructions): self
    {
        $this->instructions = $instructions;

        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product\Instruction[]
     */
    public function getInstructions(): array
    {
        return $this->instructions;
    }

    /**
     * @return string[]
     */
    public function getUniqueInstructionTypes(): array
    {
        $types = [];

        foreach ($this->getInstructions() as $instruction) {
            $types[] = $instruction->getType();
        }

        return array_unique($types);
    }

    public function hasInstructionWithType(string $instructionType): bool
    {
        return in_array($instructionType, $this->getUniqueInstructionTypes(), true);
    }

    public function hasInstructionWithTypes(array $instructionTypes): bool
    {
        return count(array_intersect($this->getUniqueInstructionTypes(), $instructionTypes)) > 0;
    }
}
