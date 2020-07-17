<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationToInnodb;

use Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationToInnodb;

/**
 * Class  \Ess\M2ePro\Controller\Adminhtml\Wizard\MigrationToInnodb\RunSynchNow
 */
class RunSynchNow extends MigrationToInnodb
{
    //########################################

    public function execute()
    {
        // @codingStandardsIgnoreLine
        session_write_close();

        $component = $this->getRequest()->getParam('component');
        /** @var \Ess\M2ePro\Model\Marketplace $marketplace */
        $marketplace = $this->activeRecordFactory->getObjectLoaded(
            'Marketplace',
            (int)$this->getRequest()->getParam('marketplace_id')
        );

        $component= ucfirst(strtolower($component));
        $synchronization = $this->modelFactory->getObject($component . '_Marketplace_Synchronization');
        $synchronization->setMarketplace($marketplace);

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
        } catch (\Exception $e) {
            $synchronization->getlog()->addMessageFromException($e);
            $synchronization->getLockItemManager()->remove();

            $this->setJsonContent(['result' => 'error']);
            return $this->getResult();
        }

        $this->setJsonContent(['result' => 'success']);
        return $this->getResult();
    }

    //########################################
}
