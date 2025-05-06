<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Magento\Product\Rule\Condition;

class MsiSource extends AbstractModel
{
    private \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository;
    private \Magento\Inventory\Model\ResourceModel\SourceItem $sourceItemResource;

    public function __construct(
        \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository,
        \Magento\Inventory\Model\ResourceModel\SourceItem $sourceItemResource,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Rule\Model\Condition\Context $context,
        array $data = []
    ) {
        $this->sourceRepository = $sourceRepository;
        $this->sourceItemResource = $sourceItemResource;
        parent::__construct($helperData, $helperFactory, $modelFactory, $context, $data);
    }

    public function loadAttributeOptions(): self
    {
        $result = $this->sourceRepository->getList();
        $attributes = [];
        foreach ($result->getItems() as $item) {
            $attributes[$item->getSourceCode()] = __('MSI Source: %source_name', [
               'source_name' => $item->getName(),
            ]);
        }

        $this->setAttributeOption($attributes);

        return parent::loadAttributeOptions();
    }

    public function validate(\Magento\Framework\DataObject $object): bool
    {
        return $this->validateAttribute($object->getData($this->getFieldName()));
    }

    public function collectValidatedAttributes(
        \Ess\M2ePro\Model\ResourceModel\MSI\Magento\Product\Collection $productCollection
    ): self {
        if ($productCollection->hasJoinField($this->getFieldName())) {
            return $this;
        }

        $tableAlias = $this->getTableAlias();

        $productCollection->joinTable(
            [$tableAlias => $this->sourceItemResource->getMainTable()],
            "sku = sku",
            [
                $this->getFieldName() => new \Zend_Db_Expr(
                    sprintf("IFNULL(`%s`.`quantity`, 0)", $tableAlias)
                ),
            ],
            sprintf(
                "`%s`.`source_code` = '%s'",
                $tableAlias,
                $this->getAttribute()
            ),
            'left'
        );

        return $this;
    }

    public function getInputType(): string
    {
        return 'numeric';
    }

    private function getTableAlias(): string
    {
        return sprintf(
            'si_%s',
            $this->helperData->md5String($this->getAttribute())
        );
    }

    private function getFieldName(): string
    {
        return sprintf(
            'si_%s_qty',
            $this->helperData->md5String($this->getAttribute())
        );
    }
}
