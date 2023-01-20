<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Order\Shipment;

interface ItemToShipLoaderInterface
{
    /**
     * @return array
     * @throws \Exception
     */
    public function loadItem();
}
