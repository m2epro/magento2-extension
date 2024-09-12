<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace\Sync;

class UpdateDetails extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace implements
    \Magento\Framework\App\Action\HttpPostActionInterface
{
    private \Ess\M2ePro\Model\Amazon\Dictionary\MarketplaceService $dictionaryMarketplaceService;
    private \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory;
    /**
     * @var \Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace\Sync\MarketplaceLoader
     */
    private MarketplaceLoader $amazonMarketplaceLoader;

    public function __construct(
        \Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace\Sync\MarketplaceLoader $amazonMarketplaceLoader,
        \Ess\M2ePro\Model\Amazon\Dictionary\MarketplaceService $dictionaryMarketplaceService,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->dictionaryMarketplaceService = $dictionaryMarketplaceService;
        $this->jsonResultFactory = $jsonResultFactory;
        $this->amazonMarketplaceLoader = $amazonMarketplaceLoader;
    }

    public function execute()
    {
        $marketplace = $this->amazonMarketplaceLoader->load($this->getRequest()->getParam('marketplace_id'));

        $this->dictionaryMarketplaceService->update($marketplace);

        return $this->jsonResultFactory->create()->setData([]);
    }
}
