<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType;

class GetCategories extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType
{
    /** @var \Ess\M2ePro\Model\Amazon\ProductType\CategoryFinder */
    private $categoryFinder;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Ess\M2ePro\Model\Amazon\ProductType\CategoryFinder $categoryFinder
    ) {
        parent::__construct($amazonFactory, $context);

        $this->categoryFinder = $categoryFinder;
    }

    public function execute()
    {
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        $criteriaRequest = $this->getRequest()->getParam('criteria');

        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
        if ($marketplaceId === null) {
            $result->setData('error', true);
            $result->setData('error_messages', __('Invalid input'));

            return $result;
        }

        $criteria = $criteriaRequest !== null ? \Ess\M2ePro\Helper\Json::decode($criteriaRequest) : [];
        $categories = $this->categoryFinder->find((int)$marketplaceId, $criteria);

        $jsonItems = [];
        foreach ($categories as $category) {
            $item = [
                'name' => $category->getName(),
                'path' => array_merge($criteria, [$category->getName()]),
                'isLeaf' => $category->getIsLeaf(),
                'productTypes' => [],
            ];

            foreach ($category->getProductTypes() as $productType) {
                $item['productTypes'][] = [
                    'title' => $productType->getTitle(),
                    'nick' => $productType->getNick(),
                    'templateId' => $productType->getTemplateId(),
                    'path' => array_merge($item['path'], [$productType->getTitle()]),
                ];
            }

            $jsonItems[] = $item;
        }

        $result->setData(['items' => $jsonItems]);

        return $result;
    }
}
