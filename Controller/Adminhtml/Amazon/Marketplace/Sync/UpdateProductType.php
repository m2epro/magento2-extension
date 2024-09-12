<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace\Sync;

class UpdateProductType extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace implements
    \Magento\Framework\App\Action\HttpPostActionInterface
{
    private \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory;
    private \Ess\M2ePro\Model\Amazon\Dictionary\ProductType\Repository $dictionaryProductTypeRepository;
    private \Ess\M2ePro\Model\Amazon\Dictionary\ProductTypeService $dictionaryProductTypeService;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Dictionary\ProductTypeService $dictionaryProductTypeService,
        \Ess\M2ePro\Model\Amazon\Dictionary\ProductType\Repository $dictionaryProductTypeRepository,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->dictionaryProductTypeRepository = $dictionaryProductTypeRepository;
        $this->dictionaryProductTypeService = $dictionaryProductTypeService;
    }

    public function execute()
    {
        $productTypeId = $this->getRequest()->getParam('id');
        if ($productTypeId === null) {
            throw new \RuntimeException('Missing Product Type ID');
        }

        $productType = $this->dictionaryProductTypeRepository->get((int)$productTypeId);

        $this->dictionaryProductTypeService->update($productType);

        return $this->jsonResultFactory->create()->setData([]);
    }
}
