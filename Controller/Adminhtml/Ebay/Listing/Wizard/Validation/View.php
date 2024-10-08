<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Validation;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\StepAbstract;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclarationCollectionFactory;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Model\Listing;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Product\ValidationStep\Grid;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Product\ValidationStep;

class View extends StepAbstract
{
    use WizardTrait;

    protected function getStepNick(): string
    {
        return StepDeclarationCollectionFactory::STEP_VALIDATION;
    }

    protected function process(Listing $listing)
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $grid = $this->getLayout()
                         ->createBlock(
                             Grid::class,
                             '',
                             [
                               'listingProductIds' => $this->uiWizardRuntimeStorage->getManager()->getProductsIds()
                             ],
                         );
            $this->setAjaxContent($grid);

            return $this->getResult();
        }

        $this->getResultPage()
             ->getConfig()
             ->getTitle()->prepend(__('Validate Category Specifics'));

        $this->addContent(
            $this->getLayout()->createBlock(
                ValidationStep::class,
                '',
                [
                    'listing' => $this->uiWizardRuntimeStorage->getManager()->getListing()
                ],
            ),
        );

        return $this->getResult();
    }
}
