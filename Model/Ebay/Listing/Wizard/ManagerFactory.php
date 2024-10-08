<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard;

use Ess\M2ePro\Model\Ebay\Listing\Wizard;
use Magento\Framework\ObjectManagerInterface;

class ManagerFactory
{
    private ObjectManagerInterface $objectManager;

    private Repository $repository;

    private StepDeclarationCollectionFactory $stepDeclarationCollectionFactory;

    public function __construct(
        ObjectManagerInterface $objectManager,
        Repository $repository,
        StepDeclarationCollectionFactory $stepDeclarationCollectionFactory
    ) {
        $this->objectManager = $objectManager;
        $this->repository = $repository;
        $this->stepDeclarationCollectionFactory = $stepDeclarationCollectionFactory;
    }

    public function create(Wizard $wizard): Manager
    {
        $stepCollection = $this->stepDeclarationCollectionFactory->create($wizard->getType());

        return $this->objectManager->create(Manager::class, ['wizard' => $wizard, 'stepCollection' => $stepCollection]);
    }

    public function createById(int $id): Manager
    {
        return $this->create($this->repository->get($id));
    }
}
