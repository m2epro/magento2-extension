<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Order\Tax;

interface ProductPriceTaxInterface
{
    /**
     * @return float|int
     */
    public function getTaxRateValue();

    /**
     * @return float|int
     */
    public function getNotRoundedTaxRateValue();

    /**
     * @return bool
     */
    public function isEnabledRoundingOfTaxRateValue(): bool;
}
