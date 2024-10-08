<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Walmart\ProductType;

class Service
{
    private Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function deleteByIds(array $idsProductTypes): Service\ResultOfMassDelete
    {
        $result = new Service\ResultOfMassDelete();

        foreach ($idsProductTypes as $productTypeId) {
            $productType = $this->repository->find((int)$productTypeId);
            if ($productType === null) {
                continue;
            }

            if ($this->repository->isUsed($productType)) {
                $result->incrementCountLocked();
                continue;
            }

            $this->repository->delete($productType);
            $result->incrementCountDeleted();
        }

        return $result;
    }
}
