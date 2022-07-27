<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

use \Ess\M2ePro\Helper\Component\Ebay\Category as eBayCategory;

class SaveCategoryTemplate extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Module\Exception */
    private $exceptionHelper;

    /** @var \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\ConverterFactory */
    private $converterFactory;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\SnapshotBuilderFactory */
    private $snapshotBuilderFactory;

    /** @var \Magento\Framework\DB\TransactionFactory */
    private $transactionFactory;

    /** @var \Ess\M2ePro\Model\Ebay\Template\CategoryFactory */
    private $categoryFactory;

    /** @var \Ess\M2ePro\Model\Ebay\Template\StoreCategoryFactory */
    private $storeCategoryFactory;

    /** @var \Ess\M2ePro\Model\Ebay\Template\Category\BuilderFactory */
    private $categoryBuilderFactory;

    /** @var \Ess\M2ePro\Model\Ebay\Template\StoreCategory\BuilderFactory */
    private $storeCategoryBuilderFactory;

    /** @var \Ess\M2ePro\Model\Ebay\Template\AffectedListingsProducts\ProcessorFactory */
    private $processorFactory;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Exception $helperException,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Model\Ebay\Template\CategoryFactory $categoryFactory,
        \Ess\M2ePro\Model\Ebay\Template\StoreCategoryFactory $storeCategoryFactory,
        \Ess\M2ePro\Model\Ebay\Template\Category\BuilderFactory $categoryBuilderFactory,
        \Ess\M2ePro\Model\Ebay\Template\StoreCategory\BuilderFactory $storeCategoryBuilderFactory,
        \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\ConverterFactory $converterFactory,
        \Ess\M2ePro\Model\Ebay\Listing\Product\SnapshotBuilderFactory $snapshotBuilderFactory,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Ess\M2ePro\Model\Ebay\Template\AffectedListingsProducts\ProcessorFactory $changeProcessorFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->dataHelper                  = $dataHelper;
        $this->converterFactory            = $converterFactory;
        $this->snapshotBuilderFactory      = $snapshotBuilderFactory;
        $this->transactionFactory          = $transactionFactory;
        $this->exceptionHelper             = $exceptionHelper;
        $this->categoryFactory             = $categoryFactory;
        $this->storeCategoryFactory        = $storeCategoryFactory;
        $this->categoryBuilderFactory      = $categoryBuilderFactory;
        $this->storeCategoryBuilderFactory = $storeCategoryBuilderFactory;
        $this->processorFactory            = $changeProcessorFactory;
    }

    public function execute()
    {
        $categoryTemplatesData = $this->dataHelper->jsonDecode(
            $this->getRequest()->getPost('template_category_data')
        );

        if ($categoryTemplatesData === null) {
            $this->setAjaxContent('0', false);
            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection $collection */
        $collection = $this->ebayFactory->getObject('Listing_Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => $this->getRequestIds('products_id')]);
        $converter = $this->getConverter($categoryTemplatesData);

        try {
            $snapshots = $this->createSnapshots($collection, $converter);
            $this->updateProcessChanges($snapshots, $collection);
        } catch (\Exception $exception) {
            $this->exceptionHelper->process($exception);
            $this->setAjaxContent('0', false);
            return $this->getResult();
        }

        $this->setAjaxContent('1', false);
        return $this->getResult();
    }

    private function createSnapshots(
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection,
        \Ess\M2ePro\Model\Ebay\Template\Category\Chooser\Converter $converter
    ) {
        $categoryTmpl = $this->buildTemplateCategory(
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_EBAY_MAIN)
        );

        $categorySecondaryTmpl = $this->buildTemplateCategory(
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_EBAY_SECONDARY)
        );

        $storeCategoryTmpl = $this->buildStoreTemplateCategory(
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_STORE_MAIN)
        );

        $storeCategorySecondaryTmpl = $this->buildStoreTemplateCategory(
            $converter->getCategoryDataForTemplate(eBayCategory::TYPE_STORE_SECONDARY)
        );

        $snapshots   = [];
        $transaction = $this->transactionFactory->create();

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        foreach ($collection->getItems() as $listingProduct) {
            $snapshotBuilder = $this->snapshotBuilderFactory->create();
            $snapshotBuilder->setModel($listingProduct);
            $snapshots[$listingProduct->getId()] = $snapshotBuilder->getSnapshot();

            $listingProduct->getChildObject()->setData(
                'template_category_id',
                $categoryTmpl->getId()
            )->setData(
                'template_category_secondary_id',
                $categorySecondaryTmpl->getId()
            )->setData(
                'template_store_category_id',
                $storeCategoryTmpl->getId()
            )->setData(
                'template_store_category_secondary_id',
                $storeCategorySecondaryTmpl->getId()
            );

            $transaction->addObject($listingProduct);
        }

        $transaction->save();

        return $snapshots;
    }

    private function buildTemplateCategory(array $rawData)
    {
        $builder = $this->categoryBuilderFactory->create();
        return $builder->build($this->categoryFactory->create(), $rawData);
    }

    private function buildStoreTemplateCategory(array $rawData)
    {
        $builder = $this->storeCategoryBuilderFactory->create();
        return $builder->build($this->storeCategoryFactory->create(), $rawData);
    }

    private function getConverter(array $categoryTemplatesData)
    {
        $converter = $this->converterFactory->create();

        if ($accountId = $this->getRequest()->getParam('account_id')) {
            $converter->setAccountId($accountId);
        }

        if ($marketplaceId = $this->getRequest()->getParam('marketplace_id')) {
            $converter->setMarketplaceId($marketplaceId);
        }

        foreach ($categoryTemplatesData as $type => $templateData) {
            $converter->setCategoryDataFromChooser($templateData, $type);
        }

        return $converter;
    }

    private function updateProcessChanges(
        array $oldSnapshot,
        \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection
    ) {
        $changesProcessor = $this->processorFactory->create();

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        foreach ($collection->getItems() as $listingProduct) {
            $snapshotBuilder = $this->snapshotBuilderFactory->create();
            $snapshotBuilder->setModel($listingProduct);

            $changesProcessor->setListingProduct($listingProduct);
            $changesProcessor->processChanges(
                $snapshotBuilder->getSnapshot(),
                $oldSnapshot[$listingProduct->getId()]
            );
        }
    }
}
