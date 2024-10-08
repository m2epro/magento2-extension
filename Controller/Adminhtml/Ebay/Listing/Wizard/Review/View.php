<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Review;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\StepAbstract;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclarationCollectionFactory;
use Ess\M2ePro\Model\Listing;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Review;

class View extends StepAbstract
{
    use WizardTrait;

    protected function getStepNick(): string
    {
        return StepDeclarationCollectionFactory::STEP_REVIEW;
    }

    protected function process(Listing $listing)
    {
        $blockReview = $this->getLayout()->createBlock(
            Review::class,
        );

        $this->getResultPage()
             ->getConfig()
             ->getTitle()
             ->prepend(__('Congratulations'));

        $this->addContent($blockReview);

        return $this->getResult();
    }
}
