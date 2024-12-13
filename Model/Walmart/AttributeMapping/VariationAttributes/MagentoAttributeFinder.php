<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\AttributeMapping\VariationAttributes;

class MagentoAttributeFinder
{
    private \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Matcher\Attribute $attributeMatcher;
    private \Magento\Framework\App\ResourceConnection $resourceConnection;
    private \Ess\M2ePro\Helper\Module\Database\Structure $dbHelper;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Matcher\Attribute $attributeMatcher,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Module\Database\Structure $dbHelper
    ) {
        $this->attributeMatcher = $attributeMatcher;
        $this->resourceConnection = $resourceConnection;
        $this->dbHelper = $dbHelper;
    }

    /**
     * @param array $searchAttributes
     *
     * @return array{attribute_code:string, attribute_label:string}|null
     */
    public function findMagentoAttribute(array $searchAttributes): ?array
    {
        $superAttributesData = $this->getAllMagentoSuperAttributes();

        $this->attributeMatcher->canUseDictionary();
        $this->attributeMatcher->setDestinationAttributes($searchAttributes);
        $this->attributeMatcher->setSourceAttributes(array_keys($superAttributesData));

        $matched = $this->attributeMatcher->getMatchedAttributes();
        foreach ($matched as $magentoAttributeName => $matchedAttribute) {
            if ($matchedAttribute === null) {
                continue;
            }

            if (!in_array($matchedAttribute, $searchAttributes)) {
                continue;
            }

            $attributeData = $superAttributesData[$magentoAttributeName] ?? null;
            if ($attributeData === null) {
                continue;
            }

            return $attributeData;
        }

        return null;
    }

    /**
     * @return array{array{attribute_code:string, attribute_label:string}}
     */
    public function getAllMagentoSuperAttributes(): array
    {
        $select = $this->resourceConnection->getConnection()->select();
        $select->distinct();
        $select->from(
            ['sa' => $this->dbHelper->getTableNameWithPrefix('catalog_product_super_attribute')],
            []
        );
        $select->joinInner(
            ['ea' => $this->dbHelper->getTableNameWithPrefix('eav_attribute')],
            'ea.attribute_id = sa.attribute_id',
            [
                'attribute_code' => 'attribute_code',
                'attribute_label' => 'frontend_label',
            ]
        );

        $superAttributes = $select->query()->fetchAll();

        $result = [];
        foreach ($superAttributes as $attribute) {
            $result[$attribute['attribute_label']] = [
                'attribute_code' => $attribute['attribute_code'],
                'attribute_label' => $attribute['attribute_label']
            ];
        }

        return $result;
    }
}
