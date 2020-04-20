<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product\Instruction\Handler;

use Ess\M2ePro\Model\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Listing\Product\Instruction\Handler\Input
 */
class Input extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Listing\Product */
    protected $listingProduct = null;

    /** @var \Ess\M2ePro\Model\Listing\Product\Instruction[] */
    protected $instructions = [];

    //########################################

    public function setListingProduct(Product $listingProduct)
    {
        $this->listingProduct = $listingProduct;
        return $this;
    }

    public function getListingProduct()
    {
        return $this->listingProduct;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product\Instruction[] $instructions
     * @return $this
     */
    public function setInstructions(array $instructions)
    {
        $this->instructions = $instructions;
        return $this;
    }

    // ---------------------------------------

    public function getInstructions()
    {
        return $this->instructions;
    }

    // ---------------------------------------

    public function getUniqueInstructionTypes()
    {
        $types = [];

        foreach ($this->getInstructions() as $instruction) {
            $types[] = $instruction->getType();
        }

        return array_unique($types);
    }

    public function hasInstructionWithType($instructionType)
    {
        return in_array($instructionType, $this->getUniqueInstructionTypes());
    }

    public function hasInstructionWithTypes(array $instructionTypes)
    {
        return array_intersect($this->getUniqueInstructionTypes(), $instructionTypes);
    }

    //########################################
}
