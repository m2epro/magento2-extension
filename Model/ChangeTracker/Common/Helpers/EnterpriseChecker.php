<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Common\Helpers;

class EnterpriseChecker
{
    private bool $isEnterpriseEdition;
    private \Magento\Framework\App\ResourceConnection $resourceConnection;

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
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->isEnterpriseEdition)) {
            return $this->isEnterpriseEdition;
        }

        $tableName = $this->resourceConnection
            ->getTableName('catalog_product_entity_varchar');

        return $this->isEnterpriseEdition = $this->resourceConnection
            ->getConnection()
            ->tableColumnExists($tableName, 'row_id');
    }
}
