<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search\Custom;

use Ess\M2ePro\Helper\Data\Product\Identifier;

class Query
{
    /** @var string */
    private $value;

    /**
     * @param string $value
     */
    public function __construct(string $value)
    {
        $this->value = trim(str_replace('-', '', $value));
    }

    /**
     * @return bool
     */
    public function isAsin(): bool
    {
        return Identifier::isASIN($this->value);
    }

    /**
     * @return string|null
     */
    public function getOtherIdentifierType(): ?string
    {
        if (Identifier::isISBN($this->value)) {
            return Identifier::ISBN;
        }

        if (Identifier::isUPC($this->value)) {
            return Identifier::UPC;
        }

        if (Identifier::isEAN($this->value)) {
            return Identifier::EAN;
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getIdentifierType(): ?string
    {
        return $this->isAsin() ? Identifier::ASIN : $this->getOtherIdentifierType();
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}
