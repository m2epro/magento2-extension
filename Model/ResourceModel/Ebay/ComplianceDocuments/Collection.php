<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ResourceModel\Ebay\ComplianceDocuments;

/**
 * @method \Ess\M2ePro\Model\Ebay\ComplianceDocuments[] getItems()
 * @method \Ess\M2ePro\Model\Ebay\ComplianceDocuments getFirstItem()
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    public function _construct(): void
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Ebay\ComplianceDocuments::class,
            \Ess\M2ePro\Model\ResourceModel\Ebay\ComplianceDocuments::class
        );
    }
}
