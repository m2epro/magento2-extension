<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Identifiers;

use Ess\M2ePro\Helper\Data\Product\Identifier;

class WorldwideId extends IdentifierDTO
{
    /**
     * @return bool
     */
    public function isUPC(): bool
    {
        return Identifier::isUPC($this->identifier);
    }

    /**
     * @return bool
     */
    public function isEAN(): bool
    {
        return Identifier::isEAN($this->identifier);
    }

    /**
     * @return bool
     */
    public function hasResolvedType(): bool
    {
        return $this->isUPC() || $this->isEAN();
    }
}
