<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace\Sync;

class GetProductTypeList extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace implements
    \Magento\Framework\App\Action\HttpGetActionInterface
{
    private \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory;
    private \Ess\M2ePro\Model\Amazon\Dictionary\ProductType\Repository $dictionaryProductTypeRepository;
    /**
     * @var \Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace\Sync\MarketplaceLoader
     */
    private MarketplaceLoader $amazonMarketplaceLoader;

    public function __construct(
        \Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace\Sync\MarketplaceLoader $amazonMarketplaceLoader,
        \Ess\M2ePro\Model\Amazon\Dictionary\ProductType\Repository $dictionaryProductTypeRepository,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->jsonResultFactory = $jsonResultFactory;
        $this->dictionaryProductTypeRepository = $dictionaryProductTypeRepository;
        $this->amazonMarketplaceLoader = $amazonMarketplaceLoader;
    }

    public function execute()
    {
        $marketplace = $this->amazonMarketplaceLoader->load($this->getRequest()->getParam('marketplace_id'));

        $productTypes = $this->dictionaryProductTypeRepository->findValidByMarketplace($marketplace);

        $result = [];
        foreach ($productTypes as $productType) {
            $result[] = [
                'id' => (int)$productType->getId(),
                'title' => $productType->getTitle(),
            ];
        }

        return $this->jsonResultFactory->create()
                                       ->setData([
                                           'list' => $result,
                                       ]);
    }
}
