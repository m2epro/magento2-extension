<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Marketplace;

class RunSynchNow extends Marketplace
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    protected $amazonHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->amazonHelper = $amazonHelper;
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

        /** @var \Ess\M2ePro\Model\Amazon\Marketplace\Synchronization $synchronization */
        $synchronization = $this->modelFactory->getObject('Amazon_Marketplace_Synchronization');
        $synchronization->setMarketplace($marketplace);

        if ($synchronization->isLocked()) {
            $synchronization->getLog()->addMessage(
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
        } catch (\Exception $e) {
            $synchronization->getLog()->addMessageFromException($e);

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
