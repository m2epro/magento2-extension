<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\ChangeTracker\Traits;

trait AffectedMagentoProductLoaderTrait
{
    private array $affectedMagentoProductIds;
    /**
     * @return array<int>
     *
     * @throws \Zend_Db_Statement_Exception
     * @throws \Exception
     */
    protected function loadAffectedMagentoProductIds(): array
    {
        /** @psalm-suppress RedundantPropertyInitializationCheck */
        if (isset($this->affectedMagentoProductIds)) {
            return $this->affectedMagentoProductIds;
        }

        $queryResult = $this->queryBuilderFactory
            ->createSelect()
            ->distinct()
            ->addSelect('product_id', 'product.product_id')
            ->from('product', $this->productSubQuery())
            ->fetchAll();

        $result = [];
        foreach ($queryResult as $data) {
            $result[] = (int)$data['product_id'];
        }

        return $this->affectedMagentoProductIds = $result;
    }

    abstract protected function productSubQuery(): \Ess\M2ePro\Model\ChangeTracker\Common\QueryBuilder\SelectQueryBuilder;
}
