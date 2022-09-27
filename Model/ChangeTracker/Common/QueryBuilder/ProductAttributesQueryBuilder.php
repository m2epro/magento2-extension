<?php

namespace Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder;

class ProductAttributesQueryBuilder
{
    /** @var array */
    private $attributesTableMap;

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $dbHelper;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Ess\M2ePro\Helper\Module\Database\Structure $dbHelper
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Module\Database\Structure $dbHelper
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->dbHelper = $dbHelper;
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
        $attributeTable = $this->getAttributeTable($attributeCode);
        $attributeId = $this->getAttributeId($attributeCode);

        $valueQuery = sprintf(
            "SUBSTRING_INDEX(GROUP_CONCAT(value ORDER BY IF(store_id = %s, 0, 1)), ',', 1)",
            $storeIdAttribute
        );

        $attributeSelect = $this->resourceConnection->getConnection()->select();
        $attributeSelect->from(
            ['attr' => $this->dbHelper->getTableNameWithPrefix($attributeTable)],
            null
        );
        $attributeSelect->columns([
            'value' => new \Zend_Db_Expr($valueQuery)
        ]);
        $attributeSelect->where('attr.attribute_id = ?', $attributeId);
        $attributeSelect->where('attr.store_id = 0 OR attr.store_id = ?', new \Zend_Db_Expr($storeIdAttribute));
        $attributeSelect->group('attr.entity_id');

        if ($productAttribute !== null) {
            $attributeSelect->where('attr.entity_id = ?', new \Zend_Db_Expr($productAttribute));
        }

        return $attributeSelect;
    }

    /**
     * @param string $attributeCode
     *
     * @return array
     */
    private function getAttributeData(string $attributeCode): array
    {
        if ($this->attributesTableMap === null) {
            $this->loadAttributeTablesMap();
        }

        if (false === array_key_exists($attributeCode, $this->attributesTableMap)) {
            throw new \RuntimeException('Not found table for attribute ' . $attributeCode);
        }

        return $this->attributesTableMap[$attributeCode];
    }

    /**
     * @param string $attributeCode
     *
     * @return string
     */
    private function getAttributeTable(string $attributeCode): string
    {
        return $this->getAttributeData($attributeCode)['table'];
    }

    /**
     * @param string $attributeCode
     *
     * @return int
     */
    private function getAttributeId(string $attributeCode): int
    {
        return $this->getAttributeData($attributeCode)['attribute_id'];
    }

    /**
     * @return void
     */
    private function loadAttributeTablesMap(): void
    {
        $select = $this->resourceConnection->getConnection()->select();
        $select->from(
            $this->dbHelper->getTableNameWithPrefix('eav_attribute'),
            ['backend_type', 'attribute_code', 'attribute_id']
        );
        $select->where("backend_type != 'static'");

        $tmp = [];
        foreach ($select->query()->fetchAll() as $data) {
            $tmp[$data['attribute_code']] = [
                'table' => 'catalog_product_entity_' . $data['backend_type'],
                'attribute_id' => (int)$data['attribute_id'],
            ];
        }

        $this->attributesTableMap = $tmp;
    }
}
