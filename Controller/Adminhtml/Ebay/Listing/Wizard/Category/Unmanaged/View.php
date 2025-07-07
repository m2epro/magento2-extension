<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Category\Unmanaged;

use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\StepAbstract;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclarationCollectionFactory;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Model\Listing;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Model\Listing\Ui\RuntimeStorage;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Ui\RuntimeStorage as WizardRuntimeStorage;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Provider\Category\Details;

class View extends StepAbstract
{
    use WizardTrait;

    private const SESSION_DATA_KEY = 'ebay_listing_product_category_settings';

    private Details $categoryDetailsProvider;

    public function __construct(
        ManagerFactory $wizardManagerFactory,
        RuntimeStorage $uiListingRuntimeStorage,
        WizardRuntimeStorage $uiWizardRuntimeStorage,
        Details $categoryDetailsProvider,
        Context $context,
        Factory $factory
    ) {
        parent::__construct(
            $wizardManagerFactory,
            $uiListingRuntimeStorage,
            $uiWizardRuntimeStorage,
            $factory,
            $context
        );

        $this->categoryDetailsProvider = $categoryDetailsProvider;
    }

    protected function getStepNick(): string
    {
        return StepDeclarationCollectionFactory::STEP_GENERAL_SELECT_CATEGORY_STEP;
    }

    protected function process(Listing $listing)
    {
        $manager = $this->getWizardManager();
        $wizardProducts = $manager->getNotProcessedProducts();
        $listing = $manager->getListing();
        $categoriesData = $this->categoryDetailsProvider->getCategoriesDetails(
            $wizardProducts,
            $listing->getAccountId(),
            $listing->getMarketplaceId()
        );

        $block = $this
            ->getLayout()
            ->createBlock(
                Category\Manually::class,
                '',
                [
                    'categoriesData' => $categoriesData,
                ]
            );

        if ($this->getRequest()->isXmlHttpRequest()) {
            $block->getChildBlock('grid')->setCategoriesData($categoriesData);
            $this->setAjaxContent($block->getChildBlock('grid')->toHtml());

            return $this->getResult();
        }

        $this->addContent($block);

        $this->getResultPage()->getConfig()->getTitle()->prepend(
            __('Set Category (Manually for each Product)')
        );

        return $this->getResult();
    }
}
