<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search\Settings;

class CounterOfFind
{
    private const SESSION_ID = 'amazon_multiple_products_found_counter';

    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;

    public function __construct(\Ess\M2ePro\Helper\Data\Session $sessionHelper)
    {
        $this->sessionHelper = $sessionHelper;
    }

    /**
     * @return void
     */
    public function increment(): void
    {
        $count = (int)$this->sessionHelper->getValue(self::SESSION_ID);
        $this->sessionHelper->setValue(self::SESSION_ID, ++$count);
    }

    /**
     * @return int
     */
    public function getCountAndReset(): int
    {
        return (int)$this->sessionHelper->getValue(self::SESSION_ID, true);
    }
}
