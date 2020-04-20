<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator
 */
class Configurator extends \Ess\M2ePro\Model\Listing\Product\Action\Configurator
{
    const DATA_TYPE_QTY        = 'qty';
    const DATA_TYPE_LAG_TIME   = 'lag_time';
    const DATA_TYPE_PRICE      = 'price';
    const DATA_TYPE_PROMOTIONS = 'promotions';
    const DATA_TYPE_DETAILS    = 'details';

    //########################################

    /**
     * @return array
     */
    public function getAllDataTypes()
    {
        return [
            self::DATA_TYPE_QTY,
            self::DATA_TYPE_LAG_TIME,
            self::DATA_TYPE_PRICE,
            self::DATA_TYPE_PROMOTIONS,
            self::DATA_TYPE_DETAILS,
        ];
    }

    //########################################

    /**
     * @return bool
     */
    public function isQtyAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_QTY);
    }

    /**
     * @return $this
     */
    public function allowQty()
    {
        return $this->allow(self::DATA_TYPE_QTY);
    }

    /**
     * @return $this
     */
    public function disallowQty()
    {
        return $this->disallow(self::DATA_TYPE_QTY);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isLagTimeAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_LAG_TIME);
    }

    /**
     * @return $this
     */
    public function allowLagTime()
    {
        return $this->allow(self::DATA_TYPE_LAG_TIME);
    }

    /**
     * @return $this
     */
    public function disallowLagTime()
    {
        return $this->disallow(self::DATA_TYPE_LAG_TIME);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isPriceAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_PRICE);
    }

    /**
     * @return $this
     */
    public function allowPrice()
    {
        return $this->allow(self::DATA_TYPE_PRICE);
    }

    /**
     * @return $this
     */
    public function disallowPrice()
    {
        return $this->disallow(self::DATA_TYPE_PRICE);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isPromotionsAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_PROMOTIONS);
    }

    /**
     * @return $this
     */
    public function allowPromotions()
    {
        return $this->allow(self::DATA_TYPE_PROMOTIONS);
    }

    /**
     * @return $this
     */
    public function disallowPromotions()
    {
        return $this->disallow(self::DATA_TYPE_PROMOTIONS);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isDetailsAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_DETAILS);
    }

    /**
     * @return $this
     */
    public function allowDetails()
    {
        return $this->allow(self::DATA_TYPE_DETAILS);
    }

    /**
     * @return $this
     */
    public function disallowDetails()
    {
        return $this->disallow(self::DATA_TYPE_DETAILS);
    }

    //########################################
}
