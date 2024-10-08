<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\ProductSource;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\StepAbstract;
use Ess\M2ePro\Model\Listing;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclarationCollectionFactory;

class View extends StepAbstract
{
    protected function getStepNick(): string
    {
        return StepDeclarationCollectionFactory::STEP_SELECT_PRODUCT_SOURCE;
    }

    protected function process(Listing $listing)
    {
        $this->addContent(
            $this->getLayout()->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\ProductSourceSelect::class,
            ),
        );

        $this->getResultPage()
             ->getConfig()
             ->getTitle()
             ->prepend(__('Add Magento Products'));

        $this->setPageHelpLink('https://docs-m2.m2epro.com/');

        return $this->getResult();
    }
}
