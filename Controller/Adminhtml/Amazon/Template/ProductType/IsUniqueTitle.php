<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType;

class IsUniqueTitle extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Template\ProductType
{
    private \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $templateProductTypeRepository;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Template\ProductType\Repository $templateProductTypeRepository,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->templateProductTypeRepository = $templateProductTypeRepository;
    }

    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $title = $this->getRequest()->getParam('title');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');
        $productTypeId = $this->getRequest()->getParam('product_type_id');

        if (empty($title) || empty($marketplaceId)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('You should provide correct parameters.');
        }

        $this->setJsonContent([
            'result' => $this->isUniqueTitle($title, (int)$marketplaceId, (int)$productTypeId),
        ]);

        return $this->getResult();
    }

    private function isUniqueTitle(string $title, int $marketplaceId, int $productTypeId): bool
    {
        $exist = $this->templateProductTypeRepository->findByTitleMarketplace(
            $title,
            $marketplaceId,
            $productTypeId > 0 ? $productTypeId : null
        );

        return $exist === null;
    }
}
