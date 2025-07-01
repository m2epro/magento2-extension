<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay\PromotedListing\Campaign;

/**
 * @method \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign[] getItems()
 * @method \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign getFirstItem()
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Ebay\PromotedListing\Campaign::class,
            \Ess\M2ePro\Model\ResourceModel\Ebay\PromotedListing\Campaign::class
        );
    }
}
