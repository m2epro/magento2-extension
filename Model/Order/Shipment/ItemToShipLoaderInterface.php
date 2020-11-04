<?php

namespace Ess\M2ePro\Model\Order\Shipment;

/**
 * @package Ess\M2ePro\Model\Order\Shipment
 */
interface ItemToShipLoaderInterface
{
    //########################################

    /**
     * @return array
     * @throws \Exception
     */
    public function loadItem();

    //########################################
}
