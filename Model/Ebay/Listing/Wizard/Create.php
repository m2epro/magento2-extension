<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard;

use Ess\M2ePro\Model\Listing;
use Ess\M2ePro\Model\Ebay\Listing\Wizard;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\DeleteService;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\StepFactory;
use Ess\M2ePro\Model\Ebay\Listing\WizardFactory;

class Create
{
    private Repository $wizardRepository;

    private WizardFactory $wizardFactory;

    private StepFactory $stepFactory;

    private StepDeclarationCollectionFactory $stepDeclarationCollectionFactory;

    private DeleteService $deleteService;

    public function __construct(
        Repository $wizardRepository,
        WizardFactory $wizardFactory,
        StepFactory $stepFactory,
        StepDeclarationCollectionFactory $stepDeclarationCollectionFactory,
        DeleteService $deleteService
    ) {
        $this->wizardRepository = $wizardRepository;
        $this->wizardFactory = $wizardFactory;
        $this->stepFactory = $stepFactory;
        $this->stepDeclarationCollectionFactory = $stepDeclarationCollectionFactory;
        $this->deleteService = $deleteService;
    }

    public function process(Listing $listing, string $type): Wizard
    {
        Wizard::validateType($type);

        $existWizard = $this->wizardRepository->findNotCompletedByListingAndType($listing, $type);

        if ($existWizard !== null) {
            return $existWizard;
        }

        $stepsDeclaration = $this->stepDeclarationCollectionFactory->create($type);

        $wizard = $this->wizardFactory->create()
                                      ->init($listing, $type, $stepsDeclaration->getFirst()->getNick());

        $this->wizardRepository->create($wizard);

        $steps = [];
        foreach ($stepsDeclaration->getAll() as $stepDeclaration) {
            $steps[] = $this->stepFactory->create()
                                         ->init($wizard, $stepDeclaration->getNick());
        }

        $this->wizardRepository->createSteps($steps);

        $wizard->initSteps($steps);

        $this->deleteService->removeOld();

        return $wizard;
    }
}
