<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay;

class ComplianceDocuments extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\AbstractModel
{
    public const COLUMN_ID = 'id';
    public const COLUMN_ACCOUNT_ID = 'account_id';
    public const COLUMN_HASH = 'hash';
    public const COLUMN_TYPE = 'type';
    public const COLUMN_URL = 'url';
    public const COLUMN_STATUS = 'status';
    public const COLUMN_DOCUMENT_ID = 'document_id';
    public const COLUMN_ERROR = 'error';
    public const COLUMN_UPDATE_DATE = 'update_date';
    public const COLUMN_CREATE_DATE = 'create_date';

    protected function _construct(): void
    {
        $this->_init(
            \Ess\M2ePro\Helper\Module\Database\Tables::TABLE_EBAY_COMPLIANCE_DOCUMENTS,
            self::COLUMN_ID
        );
    }
}
