<?php

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationToInnodb;

use Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationToInnodb;
use Ess\M2ePro\Model\Walmart\Marketplace\WithProductType\SynchronizationFactory as WalmartWithProductTypeSyncFactory;

class RunSynchNow extends MigrationToInnodb
{
    private \Ess\M2ePro\Model\Amazon\Dictionary\MarketplaceService $amazonDictionaryMarketplaceService;
    private \Ess\M2ePro\Model\Walmart\Marketplace\SynchronizationFactory $walmartSyncFactory;
    private WalmartWithProductTypeSyncFactory $walmartWithProductTypeSyncFactory;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Dictionary\MarketplaceService $amazonDictionaryMarketplaceService,
        \Ess\M2ePro\Model\Walmart\Marketplace\SynchronizationFactory $walmartSyncFactory,
        \Ess\M2ePro\Model\Walmart\Marketplace\WithProductType\SynchronizationFactory $walmartWithProductTypeSyncFactory,
        \Magento\Framework\Code\NameBuilder $nameBuilder,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($nameBuilder, $context);

        $this->amazonDictionaryMarketplaceService = $amazonDictionaryMarketplaceService;
        $this->walmartSyncFactory = $walmartSyncFactory;
        $this->walmartWithProductTypeSyncFactory = $walmartWithProductTypeSyncFactory;
    }

    public function execute()
    {
        $component = $this->getRequest()->getParam('component');
        /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
        $marketplace = $this->activeRecordFactory->getObjectLoaded(
            'Marketplace',
            (int)$this->getRequest()->getParam('marketplace_id')
        );

        if (strtolower($component) === 'amazon') {
            try {
                $this->amazonDictionaryMarketplaceService->update($marketplace);

                $this->setJsonContent(['result' => 'success']);
            } catch (\Throwable $e) {
                $this->setJsonContent(['result' => 'error']);
            }

            return $this->getResult();
        }

        // @codingStandardsIgnoreLine
        session_write_close();

        if ($component === 'walmart') {
            $synchronization = $this->getWalmartSyncService($marketplace);
        } else {
            $component = ucfirst(strtolower($component));

            /** @var \Ess\M2ePro\Model\Ebay\Marketplace\Synchronization $synchronization */
            $synchronization = $this->modelFactory->getObject($component . '_Marketplace_Synchronization');
            $synchronization->setMarketplace($marketplace);
        }

        if ($synchronization->isLocked()) {
            $synchronization->getlog()->addMessage(
                $this->__(
                    'Marketplaces cannot be updated now. '
                    . 'Please wait until another marketplace synchronization is completed, then try again.'
                ),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
            );

            $this->setJsonContent(['result' => 'error']);

            return $this->getResult();
        }

        try {
            $synchronization->process();
        } catch (\Throwable $e) {
            $synchronization->getlog()->addMessageFromException($e);
            $synchronization->getLockItemManager()->remove();

            $this->setJsonContent(['result' => 'error']);

            return $this->getResult();
        }

        $this->setJsonContent(['result' => 'success']);

        return $this->getResult();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Marketplace\Synchronization|\Ess\M2ePro\Model\Walmart\Marketplace\WithProductType\Synchronization
     */
    private function getWalmartSyncService(\Ess\M2ePro\Model\Marketplace $marketplace)
    {
        $sync = $this->walmartSyncFactory->create();
        if ($sync->isMarketplaceAllowed($marketplace)) {
            $sync->setMarketplace($marketplace);

            return $sync;
        }

        $syncWithPt = $this->walmartWithProductTypeSyncFactory->create();
        $syncWithPt->setMarketplace($marketplace);

        return $syncWithPt;
    }
}
