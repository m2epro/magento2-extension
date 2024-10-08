<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\Product;

use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\ProductSourceSelect;
use Ess\M2ePro\Controller\Adminhtml\Context;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\StepAbstract;
use Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Wizard\WizardTrait;
use Ess\M2ePro\Helper\Data\GlobalData;
use Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory;
use Ess\M2ePro\Model\Listing;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Manager;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\ManagerFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\StepDeclarationCollectionFactory;
use Ess\M2ePro\Model\Ebay\Listing\Wizard\Ui\RuntimeStorage as WizardRuntimeStorage;
use Ess\M2ePro\Model\Listing\Ui\RuntimeStorage as ListingRuntimeStorage;
use Ess\M2ePro\Model\Magento\Product\RuleFactory;
use Ess\M2ePro\Model\Magento\Product\Rule;

class View extends StepAbstract
{
    use WizardTrait;

    private GlobalData $globalDataHelper;
    private RuleFactory $magentoProductRuleFactory;
    private Manager $manager;

    private \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory $viewStateFactory;

    private \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager $ruleViewStateManager;

    public function __construct(
        GlobalData $globalDataHelper,
        RuleFactory $magentoProductRuleFactory,
        ManagerFactory $wizardManagerFactory,
        ListingRuntimeStorage $uiListingRuntimeStorage,
        WizardRuntimeStorage $uiWizardRuntimeStorage,
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewStateFactory $viewStateFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule\ViewState\Manager $ruleViewStateManager,
        Factory $factory,
        Context $context
    ) {
        parent::__construct(
            $wizardManagerFactory,
            $uiListingRuntimeStorage,
            $uiWizardRuntimeStorage,
            $factory,
            $context
        );

        $this->globalDataHelper = $globalDataHelper;
        $this->magentoProductRuleFactory = $magentoProductRuleFactory;
        $this->viewStateFactory = $viewStateFactory;
        $this->ruleViewStateManager = $ruleViewStateManager;
    }

    protected function getStepNick(): string
    {
        return StepDeclarationCollectionFactory::STEP_SELECT_PRODUCTS;
    }

    protected function process(Listing $listing)
    {
        $this->manager = $this->getWizardManager();

        $data = $this->manager->getStepData(StepDeclarationCollectionFactory::STEP_SELECT_PRODUCT_SOURCE);

        $source = $data['source'];

        if ($source === ProductSourceSelect::MODE_PRODUCT) {
            return $this->showGridByCatalog(
                $listing,
                $source,
            );
        }

        if ($source === ProductSourceSelect::MODE_CATEGORY) {
            return $this->showGridByCategories(
                $listing,
                $source,
            );
        }

        throw new \LogicException('Unknown source type.');
    }

    private function showGridByCatalog(Listing $listing, string $source)
    {
        $this->setRuleData('ebay_product_add_step_one', $listing);

        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->setAjaxContent(
                $this->getLayout()
                     ->createBlock(
                         \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Product\Add\Grid::class,
                     )
                     ->toHtml(),
            );

            return $this->getResult();
        }

        $this->getResultPage()
             ->getConfig()
             ->getTitle()
             ->prepend(__('Select Magento Products'));

        $this->addContent(
            $this->getLayout()->createBlock(
                \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Product\Add::class,
                '',
                [
                    'sourceMode' => $source,
                ],
            ),
        );

        return $this->getResult();
    }

    private function showGridByCategories(Listing $listing, string $source)
    {
        $this->setRuleData('ebay_product_add_step_one', $listing);

        $data = $this->manager->getStepData($this->getStepNick());
        $selectedProductsIds = $data['products_ids'] ?? [];

        if ($this->getRequest()->isXmlHttpRequest()) {
            if ($this->getRequest()->getParam('current_category_id')) {
                $data['current_category_id'] = $this->getRequest()->getParam('current_category_id');

                $this->manager->setStepData($this->getStepNick(), $data);
            }

            $grid = $this->getLayout()
                         ->createBlock(
                             \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Product\Add\Category\Grid::class,
                         );

            $grid->setSelectedIds($selectedProductsIds);
            $grid->setCurrentCategoryId($data['current_category_id']);

            $this->setAjaxContent($grid->toHtml());

            return $this->getResult();
        }

        $this->setPageHelpLink('https://docs-m2.m2epro.com/');

        $this->getResultPage()
             ->getConfig()
             ->getTitle()->prepend(__('Select Magento Products'));

        $gridContainer = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Product\Add::class,
            '',
            [
                'sourceMode' => $source,
            ],
        );
        $this->addContent($gridContainer);

        $treeBlock = $this->getLayout()
                          ->createBlock(
                              \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Wizard\Category\Add\Tree::class,
                          );

        if (empty($data['current_category_id'])) {
            $currentNode = $treeBlock->getRoot()->getChildren()->getIterator()->current();
            if (!$currentNode) {
                throw new \Ess\M2ePro\Model\Exception('No Categories found');
            }

            $data['current_category_id'] = $currentNode->getId();
            $this->manager->setStepData($this->getStepNick(), $data);
        }

        $treeBlock->setGridId($gridContainer->getChildBlock('grid')->getId());
        $treeBlock->setSelectedIds($selectedProductsIds);
        $treeBlock->setCurrentNodeById($data['current_category_id']);

        $gridContainer->getChildBlock('grid')->setTreeBlock($treeBlock);
        $gridContainer->getChildBlock('grid')->setSelectedIds($selectedProductsIds);
        $gridContainer->getChildBlock('grid')->setCurrentCategoryId($data['current_category_id']);

        return $this->getResult();
    }

    private function setRuleData(string $prefix, Listing $listing): void
    {
        $storeId = $listing->getStoreId();
        $viewKey = $this->buildPrefix('ebay_product_add_step_one_' . Rule::NICK);

        $this->globalDataHelper->setValue(
            'rule_prefix',
            $prefix,
        );

        $getRuleBySessionData = function () use ($prefix, $storeId) {
            return $this->createRuleBySessionData();
        };

        $ruleModel = $this->ruleViewStateManager->getRuleWithViewState(
            $this->viewStateFactory->create($viewKey),
            Rule::NICK,
            $getRuleBySessionData,
            $storeId
        );

        $this->globalDataHelper->setValue(
            'rule_model',
            $ruleModel,
        );
    }

    private function createRuleBySessionData(): \Ess\M2ePro\Model\Magento\Product\Rule
    {
        $prefix = $this->buildPrefix('ebay_product_add_step_one');
        $this->globalDataHelper->setValue('rule_prefix', $prefix);
        $storeId = $this->manager->getListing()->getStoreId();

        $ruleModel = $this->magentoProductRuleFactory->create($prefix, $storeId);

        $ruleParam = $this->getRequest()->getPost('rule');

        if (!empty($ruleParam)) {
            $this->getHelper('Data\Session')->setValue(
                $prefix,
                $ruleModel->getSerializedFromPost($this->getRequest()->getPostValue())
            );
        } elseif ($ruleParam !== null) {
            $this->getHelper('Data\Session')->setValue($prefix, []);
        }

        $sessionRuleData = $this->getHelper('Data\Session')->getValue($prefix);
        if (!empty($sessionRuleData)) {
            $ruleModel->loadFromSerialized($sessionRuleData);
        }

        return $ruleModel;
    }

    private function buildPrefix(string $root): string
    {
        $listing = $this->manager->getListing();

        return $root . (isset($listing['id']) ? '_' . $listing['id'] : '');
    }
}
