<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action;

use Ess\M2ePro\Model\Exception\Logic;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Configurator
 */
class Configurator extends \Ess\M2ePro\Model\Listing\Product\Action\Configurator
{
    const DATA_TYPE_GENERAL = 'general';
    const DATA_TYPE_QTY = 'qty';
    const DATA_TYPE_PRICE = 'price';
    const DATA_TYPE_TITLE = 'title';
    const DATA_TYPE_SUBTITLE = 'subtitle';
    const DATA_TYPE_DESCRIPTION = 'description';
    const DATA_TYPE_IMAGES = 'images';
    const DATA_TYPE_CATEGORIES = 'categories';
    const DATA_TYPE_SHIPPING = 'shipping';
    const DATA_TYPE_PAYMENT = 'payment';
    const DATA_TYPE_RETURN = 'return';
    const DATA_TYPE_OTHER = 'other';
    const DATA_TYPE_VARIATIONS = 'variations';

    //########################################

    /**
     * @return array
     */
    public function getAllDataTypes()
    {
        return [
            self::DATA_TYPE_GENERAL,
            self::DATA_TYPE_QTY,
            self::DATA_TYPE_PRICE,
            self::DATA_TYPE_TITLE,
            self::DATA_TYPE_SUBTITLE,
            self::DATA_TYPE_DESCRIPTION,
            self::DATA_TYPE_IMAGES,
            self::DATA_TYPE_CATEGORIES,
            self::DATA_TYPE_SHIPPING,
            self::DATA_TYPE_PAYMENT,
            self::DATA_TYPE_RETURN,
            self::DATA_TYPE_OTHER,
            self::DATA_TYPE_VARIATIONS,
        ];
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
    public function isCategoriesAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_CATEGORIES);
    }

    /**
     * @return $this
     */
    public function allowCategories()
    {
        return $this->allow(self::DATA_TYPE_CATEGORIES);
    }

    /**
     * @return $this
     */
    public function disallowCategories()
    {
        return $this->disallow(self::DATA_TYPE_CATEGORIES);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isShippingAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_SHIPPING);
    }

    /**
     * @return $this
     */
    public function allowShipping()
    {
        return $this->allow(self::DATA_TYPE_SHIPPING);
    }

    /**
     * @return $this
     */
    public function disallowShipping()
    {
        return $this->disallow(self::DATA_TYPE_SHIPPING);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isPaymentAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_PAYMENT);
    }

    /**
     * @return $this
     */
    public function allowPayment()
    {
        return $this->allow(self::DATA_TYPE_PAYMENT);
    }

    /**
     * @return $this
     */
    public function disallowPayment()
    {
        return $this->disallow(self::DATA_TYPE_PAYMENT);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isReturnAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_RETURN);
    }

    /**
     * @return $this
     */
    public function allowReturn()
    {
        return $this->allow(self::DATA_TYPE_RETURN);
    }

    /**
     * @return $this
     */
    public function disallowReturn()
    {
        return $this->disallow(self::DATA_TYPE_RETURN);
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isOtherAllowed()
    {
        return $this->isAllowed(self::DATA_TYPE_OTHER);
    }

    /**
     * @return $this
     */
    public function allowOther()
    {
        return $this->allow(self::DATA_TYPE_OTHER);
    }

    /**
     * @return $this
     */
    public function disallowOther()
    {
        return $this->disallow(self::DATA_TYPE_OTHER);
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
}
