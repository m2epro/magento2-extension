<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Listing\Product\Variation;

class Option extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Parent\AbstractModel
{
    public const COLUMN_CHANNEL_OPTION = 'channel_option';

    public function _construct()
    {
        $this->_init('m2epro_listing_product_variation_option', 'id');
    }
}
