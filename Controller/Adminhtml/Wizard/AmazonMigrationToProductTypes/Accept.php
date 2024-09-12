<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\AmazonMigrationToProductTypes;

class Accept extends \Ess\M2ePro\Controller\Adminhtml\Wizard\AmazonMigrationToProductTypes
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory */
    private $marketplaceCollectionFactory;
    /** @var \Ess\M2ePro\Model\Servicing\DispatcherFactory */
    private $servicingDispatcherFactory;

    /** @var string|null */
    private $errorMessage = null;
    private \Ess\M2ePro\Model\Amazon\Dictionary\MarketplaceService $amazonDictionaryMarketplaceService;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Dictionary\MarketplaceService $amazonDictionaryMarketplaceService,
        \Ess\M2ePro\Model\ResourceModel\Marketplace\CollectionFactory $marketplaceCollectionFactory,
        \Ess\M2ePro\Model\Servicing\DispatcherFactory $servicingDispatcherFactory,
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($magentoHelper, $nameBuilder, $context);
        $this->marketplaceCollectionFactory = $marketplaceCollectionFactory;
        $this->servicingDispatcherFactory = $servicingDispatcherFactory;
        $this->amazonDictionaryMarketplaceService = $amazonDictionaryMarketplaceService;
    }

    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            $this->setJsonContent([
                'success' => false,
                'message' => 'Incorrect request type.',
            ]);

            return $this->getResult();
        }

        if ($this->isNotStarted() || $this->isActive()) {
            if (!$this->updateMarketplacesBuild()) {
                $this->setJsonContent([
                    'success' => false,
                    'message' => $this->errorMessage,
                ]);

                return $this->getResult();
            }

            $this->setStatus(\Ess\M2ePro\Helper\Module\Wizard::STATUS_COMPLETED);
        }

        $this->setJsonContent([
            'success' => true,
            'url' => $this->getUrl('*/amazon_listing/index'),
        ]);

        return $this->getResult();
    }

    private function updateMarketplacesBuild(): bool
    {
        session_write_close();

        $marketplaceCollection = $this->marketplaceCollectionFactory->create()
            ->addFieldToFilter('component_mode', \Ess\M2ePro\Helper\Component\Amazon::NICK)
            ->addFieldToFilter('status', 1);

        foreach ($marketplaceCollection->getItems() as $item) {
            if (!$this->updateMarketplaceBuild($item)) {
                return false;
            }
        }

        return true;
    }

    private function updateMarketplaceBuild(\Ess\M2ePro\Model\Marketplace $marketplace): bool
    {
        try {
            $this->amazonDictionaryMarketplaceService->update($marketplace);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();

            $this->servicingDispatcherFactory->create()->processTask(
                \Ess\M2ePro\Model\Servicing\Task\License::NAME
            );

            return false;
        }

        return true;
    }
}
