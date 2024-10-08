<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\ProductType;

class GetCategories extends \Ess\M2ePro\Controller\Adminhtml\Walmart\AbstractProductType
{
    private \Ess\M2ePro\Model\Walmart\Dictionary\Category\Repository $categoryDictionaryRepository;
    private \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Dictionary\Category\Repository $categoryDictionaryRepository,
        \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->categoryDictionaryRepository = $categoryDictionaryRepository;
        $this->productTypeRepository = $productTypeRepository;
    }

    public function execute()
    {
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        $parentCategoryId = $this->getRequest()->getParam('parent_category_id');

        $jsonResponse = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        if ($marketplaceId === null) {
            return $jsonResponse->setData('error', true)
                                ->setData('error_messages', (string)__('Invalid input'));
        }

        $categories = $this->getCategories(
            (int)$marketplaceId,
            $parentCategoryId !== null ? (int)$parentCategoryId : null
        );

        return $jsonResponse->setData(['categories' => $categories]);
    }

    private function getCategories(int $marketplaceId, ?int $parentCategoryId): array
    {
        $categories = $parentCategoryId === null
            ? $this->categoryDictionaryRepository->findRoots($marketplaceId)
            : $this->categoryDictionaryRepository->findChildren($parentCategoryId);

        $productTypes = $this->productTypeRepository->retrieveListWithKeyNick($marketplaceId);

        $resultItems = [];
        foreach ($categories as $category) {
            $item = [
                'id' => $category->getCategoryId(),
                'name' => $category->getTitle(),
                'is_leaf' => $category->isLeaf(),
                'product_types' => [],
            ];

            if ($category->isLeaf()) {
                $item['product_types'][] = [
                    'title' => $category->getProductTypeTitle(),
                    'nick' => $category->getProductTypeNick(),
                    'template_id' => isset($productTypes[$category->getProductTypeNick()])
                        ? $productTypes[$category->getProductTypeNick()]->getId()
                        : null,
                ];
            }

            $resultItems[] = $item;
        }

        return $resultItems;
    }
}
