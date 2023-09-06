<?php

namespace Ess\M2ePro\Model\Amazon\Marketplace;

class Updater
{
    /** @var \Ess\M2ePro\Model\Amazon\Marketplace\Synchronization */
    protected $synchronization;
    /** @var \Ess\M2ePro\Model\Servicing\Dispatcher */
    protected $dispatcher;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Marketplace\Synchronization $synchronization,
        \Ess\M2ePro\Model\Servicing\Dispatcher $dispatcher
    ) {
        $this->dispatcher = $dispatcher;
        $this->synchronization = $synchronization;
    }

    public function update(\Ess\M2ePro\Model\Marketplace $marketplace): bool
    {
        $synchronization = $this->synchronization;
        $synchronization->setMarketplace($marketplace);

        if ($synchronization->isLocked()) {
            $synchronization->getLog()->addMessage(
                'Marketplaces cannot be updated now. '
                    . 'Please wait until another marketplace synchronization is completed, then try again.',
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
            );
            return false;
        }

        try {
            $synchronization->process();
        } catch (\Exception $e) {
            $synchronization->getLog()->addMessageFromException($e);

            $synchronization->getLockItemManager()->remove();

            $this->dispatcher->processTask(
                \Ess\M2ePro\Model\Servicing\Task\License::NAME
            );

            return false;
        }

        return true;
    }
}
