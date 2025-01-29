<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder;

class ProductAttributesQueryBuilder
{
    private \Magento\Framework\App\ResourceConnection $resourceConnection;
    private \Ess\M2ePro\Helper\Module\Database\Structure $dbHelper;
    private \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\EnterpriseChecker $enterpriseChecker;
    private \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\MagentoAttributes $magentoAttributes;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Module\Database\Structure $dbHelper,
        \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\EnterpriseChecker $enterpriseChecker,
        \Ess\M2ePro\Model\ChangeTracker\Common\Helpers\MagentoAttributes $magentoAttributes
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->dbHelper = $dbHelper;
        $this->enterpriseChecker = $enterpriseChecker;
        $this->magentoAttributes = $magentoAttributes;
    }

    /**
     * @param string $attributeCode
     * @param string $storeIdAttribute
     * @param mixed $productAttribute
     *
     * @return \Magento\Framework\DB\Select
     */
    public function getQueryForAttribute(
        string $attributeCode,
        string $storeIdAttribute,
        ?string $productAttribute = null
    ): \Magento\Framework\DB\Select {
        $attributeSelect = $this->resourceConnection->getConnection()->select();

        $attributeTable = $this->dbHelper->getTableNameWithPrefix(
            $this->magentoAttributes->getAttributeTable($attributeCode)
        );

        $attributeSelect
            ->from(
                ['attr' => $attributeTable],
                null
            )->columns([
                'value' => new \Zend_Db_Expr(
                    sprintf(
                        "SUBSTRING_INDEX( GROUP_CONCAT(value ORDER BY IF(store_id = %s, 0, 1) ), ',', 1)",
                        $storeIdAttribute
                    )
                ),
            ])->where(
                'attr.attribute_id = ?',
                $this->magentoAttributes->getAttributeId($attributeCode)
            )->where(
                'attr.store_id = 0 OR attr.store_id = ?',
                new \Zend_Db_Expr($storeIdAttribute)
            )->group($this->getEntityIdColumnName());

        if ($productAttribute !== null) {
            $attributeSelect->where(
                $this->getEntityIdColumnName() . ' = ?',
                new \Zend_Db_Expr($productAttribute)
            );
        }

        return $attributeSelect;
    }

    /**
     * @return string
     */
    private function getEntityIdColumnName(): string
    {
        if ($this->enterpriseChecker->isEnterpriseEdition()) {
            return 'attr.row_id';
        }

        return 'attr.entity_id';
    }
}
