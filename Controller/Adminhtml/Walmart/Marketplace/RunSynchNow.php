<?php

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Marketplace;

class RunSynchNow extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Marketplace
{
    private \Ess\M2ePro\Model\Walmart\Marketplace\SynchronizationFactory $synchronizationFactory;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Marketplace\SynchronizationFactory $synchronizationFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->synchronizationFactory = $synchronizationFactory;
    }

    public function execute()
    {
        // @codingStandardsIgnoreLine
        session_write_close();

        /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
        $marketplace = $this->activeRecordFactory->getObjectLoaded(
            'Marketplace',
            (int)$this->getRequest()->getParam('marketplace_id')
        );

        $synchronization = $this->synchronizationFactory->create();
        if ($synchronization->isMarketplaceAllowed($marketplace)) {
            $this->setJsonContent(['result' => 'success']);

            return $this->getResult();
        }

        $synchronization->setMarketplace($marketplace);

        if ($synchronization->isLocked()) {
            $synchronization->getlog()->addMessage(
                (string)__(
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

            $this->modelFactory->getObject('Servicing\Dispatcher')->processTask(
                \Ess\M2ePro\Model\Servicing\Task\License::NAME
            );

            $this->setJsonContent(['result' => 'error']);

            return $this->getResult();
        }

        $this->setJsonContent(['result' => 'success']);

        return $this->getResult();
    }
}
