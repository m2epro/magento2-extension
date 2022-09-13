<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Identifiers;

use Ess\M2ePro\Helper\Data\Product\Identifier;

class GeneralId extends IdentifierDTO
{
    /**
     * @return bool
     */
    public function isASIN(): bool
    {
        return Identifier::isASIN($this->identifier);
    }

    /**
     * @return bool
     */
    public function isISBN(): bool
    {
        return Identifier::isISBN($this->identifier);
    }

    /**
     * @return bool
     */
    public function hasResolvedType(): bool
    {
        return $this->isASIN() || $this->isISBN();
    }
}
