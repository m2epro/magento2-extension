<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Common\Helpers;

class MagentoAttributes
{
    private const ATTRIBUTE_BACKEND_TYPE = 'static';
    private const ATTRIBUTE_ENTITY_TYPE_ID = 4;

    private const KEY_TABLE_NAME = 'table_name';
    private const KEY_ATTRIBUTE_ID = 'attribute_id';
    private const KEY_FRONTEND_INPUT = 'frontend_input';

    private const ATTRIBUTE_FRONTEND_INPUT_PRICE = 'price';

    private array $magentoAttributesData;

    private \Magento\Framework\App\ResourceConnection $resourceConnection;
    private \Ess\M2ePro\Helper\Module\Database\Structure $dbHelper;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Module\Database\Structure $dbHelper
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->dbHelper = $dbHelper;
    }

    public function isFrontendInputPrice(string $attributeCode): bool
    {
        $attributeFrontendInput = $this->getAttributeData($attributeCode)[self::KEY_FRONTEND_INPUT];

        return $attributeFrontendInput === self::ATTRIBUTE_FRONTEND_INPUT_PRICE;
    }

    public function getAttributeTable(string $attributeCode): string
    {
        return $this->getAttributeData($attributeCode)[self::KEY_TABLE_NAME];
    }

    public function getAttributeId(string $attributeCode): int
    {
        return $this->getAttributeData($attributeCode)[self::KEY_ATTRIBUTE_ID];
    }

    // ----------------------------------------

    private function getAttributeData(string $attributeCode): array
    {
        if (!isset($this->magentoAttributesData)) {
            $this->magentoAttributesData = $this->getMagentoAttributesData();
        }

        if (!array_key_exists($attributeCode, $this->magentoAttributesData)) {
            throw new \RuntimeException(
                sprintf(
                    'Not found attribute data by attribute code "%s"',
                    $attributeCode
                )
            );
        }

        return $this->magentoAttributesData[$attributeCode];
    }

    /**
     * @return array
     */
    private function getMagentoAttributesData(): array
    {
        $select = $this->resourceConnection->getConnection()->select();
        $select->from(
            $this->dbHelper->getTableNameWithPrefix('eav_attribute'),
            ['backend_type', 'attribute_code', 'attribute_id', 'frontend_input']
        );
        $select->where("backend_type != ?", self::ATTRIBUTE_BACKEND_TYPE);
        $select->where("entity_type_id = ?", self::ATTRIBUTE_ENTITY_TYPE_ID);

        $result = [];
        foreach ($select->query()->fetchAll() as $data) {
            $result[$data['attribute_code']] = [
                self::KEY_TABLE_NAME => 'catalog_product_entity_' . $data['backend_type'],
                self::KEY_ATTRIBUTE_ID => (int)$data['attribute_id'],
                self::KEY_FRONTEND_INPUT => $data['frontend_input'],
            ];
        }

        return $result;
    }
}
