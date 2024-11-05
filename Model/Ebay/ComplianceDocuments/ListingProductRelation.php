<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\ComplianceDocuments;

use Ess\M2ePro\Model\ResourceModel\Ebay\ComplianceDocuments\ListingProductRelation as Resource;

class ListingProductRelation extends \Ess\M2ePro\Model\ActiveRecord\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(Resource::class);
    }

    public function init(int $documentId, int $listingProductId): self
    {
        $this->setData(Resource::COLUMN_COMPLIANCE_DOCUMENT_ID, $documentId);
        $this->setData(Resource::COLUMN_LISTING_PRODUCT_ID, $listingProductId);

        return $this;
    }

    public function getListingProductId(): int
    {
        return (int)$this->getData(Resource::COLUMN_LISTING_PRODUCT_ID);
    }
}
