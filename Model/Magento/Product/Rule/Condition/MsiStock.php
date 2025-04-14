<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Magento\Product\Rule\Condition;

use Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface;

class MsiStock extends AbstractModel
{
    private \Magento\InventoryApi\Api\StockRepositoryInterface $stockRepository;
    /** @var \Magento\InventoryIndexer\Model\StockIndexTableNameResolverInterface */
    private $indexNameResolver;

    public function __construct(
        \Magento\InventoryApi\Api\StockRepositoryInterface $stockRepository,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Rule\Model\Condition\Context $context,
        array $data = []
    ) {
        $this->indexNameResolver = $objectManager->get(StockIndexTableNameResolverInterface::class);
        $this->stockRepository = $stockRepository;
        parent::__construct($helperData, $helperFactory, $modelFactory, $context, $data);
    }

    public function loadAttributeOptions(): self
    {
        $attributes = [];
        $result = $this->stockRepository->getList();
        foreach ($result->getItems() as $item) {
            $attributes[$item->getStockId()] = __('MSI Stock: %stock_name', [
                'stock_name' => $item->getName()
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

        $tableName = $this->indexNameResolver->execute((int)$this->getAttribute());
        $tableAlias = sprintf('stock_%s_table', $this->getAttribute());

        $productCollection->joinTable(
            [$tableAlias => $tableName],
            "sku = sku",
            [
                $this->getFieldName() => new \Zend_Db_Expr(
                    sprintf("IFNULL(`%s`.`quantity`, 0)", $tableAlias)
                )
            ],
            null,
            'left'
        );

        return $this;
    }

    public function getInputType(): string
    {
        return 'numeric';
    }

    private function getFieldName(): string
    {
        return sprintf('stock_%s_qty', $this->getAttribute());
    }
}
