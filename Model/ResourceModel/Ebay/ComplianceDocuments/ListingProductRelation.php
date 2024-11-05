<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay\ComplianceDocuments;

class ListingProductRelation extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_COMPLIANCE_DOCUMENT_ID = 'compliance_document_id';
    public const COLUMN_LISTING_PRODUCT_ID = 'listing_product_id';

    protected function _construct(): void
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_COMPLIANCE_DOCUMENTS_LISTING_PRODUCT,
            self::COLUMN_ID
        );
    }
}
