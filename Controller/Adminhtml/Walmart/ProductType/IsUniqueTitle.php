<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\ProductType;

class IsUniqueTitle extends \Ess\M2ePro\Controller\Adminhtml\Walmart\AbstractProductType
{
    private \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\ProductType\Repository $productTypeRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->productTypeRepository = $productTypeRepository;
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $title = $this->getRequest()->getParam('title');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        $productTypeId = $this->getRequest()->getParam('product_type_id');

        if (empty($title) || empty($marketplaceId)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('You should provide correct parameters.');
        }

        $productType = $this->productTypeRepository->findByTitleMarketplace(
            $title,
            (int)$marketplaceId,
            !empty($productTypeId) ? (int)$productTypeId : null
        );

        $isIsUniqueTitle = $productType === null;

        $this->setJsonContent(['result' => $isIsUniqueTitle]);

        return $this->getResult();
    }
}
