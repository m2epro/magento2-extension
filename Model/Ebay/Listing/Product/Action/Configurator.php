<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action;

use Ess\M2ePro\Model\Exception\Logic;

class Configurator extends \Ess\M2ePro\Model\Listing\Product\Action\Configurator
{
    const DATA_TYPE_GENERAL           = 'general';
    const DATA_TYPE_QTY               = 'qty';
    const DATA_TYPE_PRICE             = 'price';
    const DATA_TYPE_TITLE             = 'title';
    const DATA_TYPE_SUBTITLE          = 'subtitle';
    const DATA_TYPE_DESCRIPTION       = 'description';
    const DATA_TYPE_IMAGES            = 'images';
    const DATA_TYPE_SPECIFICS         = 'specifics';
    const DATA_TYPE_SHIPPING_SERVICES = 'shipping_services';
    const DATA_TYPE_VARIATIONS        = 'variations';

    const PRIORITY_QTY        = 50;
    const PRIORITY_VARIATION  = 50;
    const PRIORITY_PRICE      = 30;

    const PRIORITY_STOP                     = 60;
    const PRIORITY_REVISE_INSTEAD_OF_STOP   = 60;
    const PRIORITY_REVISE_INSTEAD_OF_RELIST = 20;
    const PRIORITY_RELIST                   = 20;
    const PRIORITY_LIST                     = 10;
    const PRIORITY_LOW                      = 0;

    private $priority = self::PRIORITY_LOW;

    //########################################

    /**
     * @return array
     */
    public function getAllDataTypes()
    {
        return array(
            self::DATA_TYPE_GENERAL,
            self::DATA_TYPE_QTY,
            self::DATA_TYPE_PRICE,
            self::DATA_TYPE_TITLE,
            self::DATA_TYPE_SUBTITLE,
            self::DATA_TYPE_DESCRIPTION,
            self::DATA_TYPE_IMAGES,
            self::DATA_TYPE_SPECIFICS,
            self::DATA_TYPE_SHIPPING_SERVICES,
            self::DATA_TYPE_VARIATIONS,
        );
    }

    //########################################

    /**
     * @return bool
     */
    public function isGeneralAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_GENERAL);
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product\Action\Configurator
     */
    public function allowGeneral()
    {
        return $this->allow(self::DATA_TYPE_GENERAL);
    }

    /**
     * @return $this
     */
    public function disallowGeneral()
    {
        return $this->disallow(self::DATA_TYPE_GENERAL);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isQtyAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_QTY);
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product\Action\Configurator
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
    public function isPriceAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_PRICE);
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product\Action\Configurator
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
    public function isTitleAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_TITLE);
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product\Action\Configurator
     */
    public function allowTitle()
    {
        return $this->allow(self::DATA_TYPE_TITLE);
    }

    /**
     * @return $this
     */
    public function disallowTitle()
    {
        return $this->disallow(self::DATA_TYPE_TITLE);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isSubtitleAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_SUBTITLE);
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product\Action\Configurator
     */
    public function allowSubtitle()
    {
        return $this->allow(self::DATA_TYPE_SUBTITLE);
    }

    /**
     * @return $this
     */
    public function disallowSubtitle()
    {
        return $this->disallow(self::DATA_TYPE_SUBTITLE);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isDescriptionAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_DESCRIPTION);
    }

    /**
     * @return \Ess\M2ePro\Model\Listing\Product\Action\Configurator
     */
    public function allowDescription()
    {
        return $this->allow(self::DATA_TYPE_DESCRIPTION);
    }

    /**
     * @return $this
     */
    public function disallowDescription()
    {
        return $this->disallow(self::DATA_TYPE_DESCRIPTION);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isImagesAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_IMAGES);
    }

    /**
     * @return $this
     */
    public function allowImages()
    {
        return $this->allow(self::DATA_TYPE_IMAGES);
    }

    /**
     * @return $this
     */
    public function disallowImages()
    {
        return $this->disallow(self::DATA_TYPE_IMAGES);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isSpecificsAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_SPECIFICS);
    }

    /**
     * @return $this
     */
    public function allowSpecifics()
    {
        return $this->allow(self::DATA_TYPE_SPECIFICS);
    }

    /**
     * @return $this
     */
    public function disallowSpecifics()
    {
        return $this->disallow(self::DATA_TYPE_SPECIFICS);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isShippingServicesAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_SHIPPING_SERVICES);
    }

    /**
     * @return $this
     */
    public function allowShippingServices()
    {
        return $this->allow(self::DATA_TYPE_SHIPPING_SERVICES);
    }

    /**
     * @return $this
     */
    public function disallowShippingServices()
    {
        return $this->disallow(self::DATA_TYPE_SHIPPING_SERVICES);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isVariationsAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_VARIATIONS);
    }

    /**
     * @return $this
     */
    public function allowVariations()
    {
        return $this->allow(self::DATA_TYPE_VARIATIONS);
    }

    /**
     * @return $this
     */
    public function disallowVariations()
    {
        return $this->disallow(self::DATA_TYPE_VARIATIONS);
    }

    //########################################

    public function tryToIncreasePriority($priority)
    {
        if ($this->priority >= $priority) {
            return $this;
        }

        return $this->setPriority($priority);
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    //########################################
}