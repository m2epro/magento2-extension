<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace;

use Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Marketplace\RunSynchNow
 */
class RunSynchNow extends Marketplace
{
    //########################################

    public function execute()
    {
        // @codingStandardsIgnoreLine
        session_write_close();

        /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
        $marketplace = $this->activeRecordFactory->getObjectLoaded(
            'Marketplace',
            (int)$this->getRequest()->getParam('marketplace_id')
        );

        /** @var \Ess\M2ePro\Model\Ebay\Marketplace\Synchronization $synchronization */
        $synchronization = $this->modelFactory->getObject('Ebay_Marketplace_Synchronization');
        $synchronization->setMarketplace($marketplace);

        if ($synchronization->isLocked()) {
            $synchronization->getlog()->addMessage(
                $this->__(
                    'Marketplaces cannot be updated now. '
                    . 'Please wait until another marketplace synchronization is completed, then try again.'
                ),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );

            $this->setJsonContent(['result' => 'error']);
            return $this->getResult();
        }

        try {
            $synchronization->process();
        } catch (\Exception $e) {
            $synchronization->getlog()->addMessage(
                $this->__($e->getMessage()),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );

            $synchronization->getLockItemManager()->remove();

            $this->setJsonContent(['result' => 'error']);
            return $this->getResult();
        }

        $this->setJsonContent(['result' => 'success']);
        return $this->getResult();
    }

    //########################################
}
