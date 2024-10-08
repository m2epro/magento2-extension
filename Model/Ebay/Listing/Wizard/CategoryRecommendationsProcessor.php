<?php

declare(strict_types=1);

namespace Ess\M2ePro\Model\Ebay\Listing\Wizard;

use Ess\M2ePro\Helper\Magento\AttributeSet;
use Ess\M2ePro\Model\Ebay\Template\Category as TemplateCategory;
use Ess\M2ePro\Model\Factory as ModelFactory;
use Ess\M2ePro\Helper\Component\Ebay\Category\Ebay;
use Ess\M2ePro\Model\ActiveRecord\Factory as ActiveRecordFactory;
use Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Product;
use Ess\M2ePro\Helper\Factory as HelperFactory;
use Ess\M2ePro\Helper\Component\Ebay\Category as EbayCategory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class CategoryRecommendationsProcessor
{
    private CollectionFactory $collectionFactory;

    private AttributeSet $magentoAttributeSetHelper;

    private ModelFactory $modelFactory;

    private Ebay $componentEbayCategoryEbay;

    private ActiveRecordFactory $activeRecordFactory;

    private TemplateCategoryLinkProcessor $categoryLinkProcessor;

    private HelperFactory $helperFactory;

    private Product $productResource;

    public function __construct(
        CollectionFactory $collectionFactory,
        AttributeSet $magentoAttributeSetHelper,
        ModelFactory $modelFactory,
        Ebay $componentEbayCategoryEbay,
        ActiveRecordFactory $activeRecordFactory,
        TemplateCategoryLinkProcessor $categoryLinkProcessor,
        Product $productResource,
        HelperFactory $helperFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->magentoAttributeSetHelper = $magentoAttributeSetHelper;
        $this->modelFactory = $modelFactory;
        $this->componentEbayCategoryEbay = $componentEbayCategoryEbay;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->categoryLinkProcessor = $categoryLinkProcessor;
        $this->productResource = $productResource;
        $this->helperFactory = $helperFactory;
    }

    public function process(Manager $manager): array
    {
        $result = ['failed' => 0, 'succeeded' => 0];

        $collection = $this->collectionFactory->create();
        $collection->addAttributeToSelect('name');
        $collection->joinTable(
            ['wizard_product' => $this->productResource->getMainTable()],
            sprintf(
                '%s = entity_id',
                \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Product::COLUMN_MAGENTO_PRODUCT_ID,
            ),
            [
                'wizard_product_id' => \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Product::COLUMN_ID
            ],
            sprintf(
                '{{table}}.%s = %s',
                \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Wizard\Step::COLUMN_WIZARD_ID,
                $manager->getWizardId(),
            ),
        );

        if ($collection->getSize() == 0) {
            return $result;
        }

        foreach ($collection->getItems() as $product) {
            if (($product->getData('name')) == '') {
                $result['failed']++;
                continue;
            }

            $query = $product->getData('name');

            $attributeSetId = $product->getData('attribute_set_id');

            if (!$this->magentoAttributeSetHelper->isDefault($attributeSetId)) {
                $query .= ' ' . $this->magentoAttributeSetHelper->getName($attributeSetId);
            }

            try {
                /** @var \Ess\M2ePro\Model\Ebay\Connector\Dispatcher $dispatcherObject */
                $dispatcherObject = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
                /** @var \Ess\M2ePro\Model\Ebay\Connector\Category\Get\Suggested $connectorObj */
                $connectorObj = $dispatcherObject->getConnector(
                    'category',
                    'get',
                    'suggested',
                    ['query' => $query],
                    $manager->getListing()->getMarketplaceId()
                );

                $dispatcherObject->process($connectorObj);
                $suggestions = $connectorObj->getResponseData();
            } catch (\Exception $e) {
                $exceptionHelper = $this->helperFactory->getObject('Module\Exception');
                $exceptionHelper->process($e);
                $result['failed']++;

                continue;
            }

            if (!empty($suggestions)) {
                foreach ($suggestions as $key => $suggestion) {
                    if (!is_numeric($key)) {
                        unset($suggestions[$key]);
                    }
                }
            }

            if (empty($suggestions)) {
                $result['failed']++;
                continue;
            }

            $suggestedCategory = null;
            foreach ($suggestions as $suggestion) {
                $categoryExists = $this->componentEbayCategoryEbay->exists(
                    $suggestion['category_id'],
                    $manager->getListing()->getMarketplaceId()
                );

                if ($categoryExists) {
                    $suggestedCategory = $suggestion;
                    break;
                }
            }

            if ($suggestedCategory === null) {
                $result['failed']++;
                continue;
            }

            /** @var \Ess\M2ePro\Model\Ebay\Template\Category $template */
            $template = $this->activeRecordFactory->getObject('Ebay_Template_Category');
            $template->loadByCategoryValue(
                $suggestedCategory['category_id'],
                TemplateCategory::CATEGORY_MODE_EBAY,
                $manager->getListing()->getMarketplaceId(),
                0
            );

            if (!$template->getId()) {
                $result['failed']++;
                continue;
            }

            $this->categoryLinkProcessor->process(
                $manager,
                $this->prepareTemplateArray($template, $suggestedCategory),
                [$product->getWizardProductId()]
            );

            $result['succeeded']++;
        }

        return $result;
    }

    private function prepareTemplateArray($template, $suggestedCategory): array
    {
        $templateArray = [
            'mode' => TemplateCategory::CATEGORY_MODE_EBAY,
            'value' => $suggestedCategory['category_id'],
            'path' => implode('>', $suggestedCategory['category_path']),
            'is_custom_template' => $template->getIsCustomTemplate(),
            'template_id' => $template->getId(),
            'specific' => null,
        ];

        return [EbayCategory::TYPE_EBAY_MAIN => $templateArray];
    }
}
