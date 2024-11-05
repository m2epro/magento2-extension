<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay\ComplianceDocuments\ListingProductRelation;

/**
 * @method \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ListingProductRelation[] getItems()
 * @method \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ListingProductRelation getFirstItem()
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Ebay\ComplianceDocuments\ListingProductRelation::class,
            \Ess\M2ePro\Model\ResourceModel\Ebay\ComplianceDocuments\ListingProductRelation::class
        );
    }
}
