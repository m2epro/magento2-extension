<?php

namespace Ess\M2ePro\Model\ChangeTracker\Common\Helpers;

class EnterpriseChecker
{
    /** @var ?bool */
    private $isEnterpriseEdition = null;
    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * https://magento.stackexchange.com/questions/352414/code-to-get-row-id-or-entity-id-for-catalog-product-entity-varchar-table-for-joi
     * @return bool
     */
    public function isEnterpriseEdition(): bool
    {
        if ($this->isEnterpriseEdition !== null) {
            return $this->isEnterpriseEdition;
        }

        $tableName = $this->resourceConnection
            ->getTableName('catalog_product_entity_varchar');

        return $this->isEnterpriseEdition = $this->resourceConnection
            ->getConnection()
            ->tableColumnExists($tableName, 'row_id');
    }
}
