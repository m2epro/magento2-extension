<?php

namespace Ess\M2ePro\Setup\Update\y23_m09;

use Magento\Framework\DB\Ddl\Table;

class AddAmazonProductTypeAttributeMappingTable extends \Ess\M2ePro\Model\Setup\Upgrade\Entity\AbstractFeature
{
    public function execute(): void
    {
        $this->createTable();
        $this->initData();
    }

    private function createTable()
    {
        $tableName = $this->getFullTableName('amazon_product_type_attribute_mapping');

        if ($this->installer->tableExists($tableName)) {
            return;
        }

        $shippingMethodsTable = $this
            ->getConnection()
            ->newTable($tableName)
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'primary' => true, 'nullable' => false, 'auto_increment' => true]
            )
            ->addColumn(
                'product_type_attribute_code',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Product Type attribute code'
            )
            ->addColumn(
                'product_type_attribute_name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Product Type attribute name'
            )
            ->addColumn(
                'magento_attribute_code',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Magento attribute code'
            )
            ->setComment('Amazon Product Type Attribute Mapping Table')
            ->setOption('type', 'INNODB')
            ->setOption('charset', 'utf8')
            ->setOption('collate', 'utf8_general_ci')
            ->setOption('row_format', 'dynamic');

        $this->getConnection()->createTable($shippingMethodsTable);
    }

    public function initData()
    {
        $select = $this->getConnection()->select();
        $select->from(
            ['tpt' => $this->getFullTableName('amazon_template_product_type')],
            ['settings' => 'settings']
        );
        $select->joinInner(
            ['dpt' => $this->getFullTableName('amazon_dictionary_product_type')],
            'dpt.id = tpt.dictionary_product_type_id',
            ['scheme' => 'scheme']
        );

        $stmt = $select->query();

        $insertData = [];
        while ($row = $stmt->fetch()) {
            $settings = json_decode($row['settings'], true);
            $scheme = json_decode($row['scheme'], true);

            foreach ($settings as $ptAttributeCode => $setting) {
                if ($setting[0]['mode'] !== 2) {
                    continue;
                }

                if (array_key_exists($ptAttributeCode, $insertData)) {
                    continue;
                }

                $insertData[$ptAttributeCode] = [
                    'product_type_attribute_code' => $ptAttributeCode,
                    'product_type_attribute_name' => $this->findName($ptAttributeCode, $scheme),
                    'magento_attribute_code' => $setting[0]['attribute_code'],
                ];
            }
        }

        if ($insertData === []) {
            return;
        }

        $this->getConnection()->insertMultiple(
            $this->getFullTableName('amazon_product_type_attribute_mapping'),
            $insertData
        );
    }

    private function findName(string $ptAttributeCode, array $scheme, string $parentName = ''): string
    {
        foreach ($scheme as $item) {
            $child = '';
            if (isset($item['children'])) {
                $child = $this->findName($ptAttributeCode, $item['children'], $item['name']);
            }

            if ($child !== '') {
                return $child;
            }

            $key = $item['name'];
            if ($parentName !== '') {
                $key = $parentName . '/' . $key;
            }

            if ($key !== $ptAttributeCode) {
                continue;
            }

            return $item['title'];
        }

        return '';
    }
}
