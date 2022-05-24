<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Connector\Order\Get\FinalFee;

class Response
{
    /** @var float|null */
    private $final;

    public function __construct(?float $final)
    {
        $this->final = $final;
    }

    /**
     * @return bool
     */
    public function hasFinal(): bool
    {
        return $this->final !== null;
    }

    /**
     * @return float
     */
    public function getFinal(): float
    {
        return $this->final;
    }
}
