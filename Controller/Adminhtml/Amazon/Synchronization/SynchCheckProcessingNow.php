<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Settings;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Synchronization\SynchCheckProcessingNow
 */
class SynchCheckProcessingNow extends Settings
{
    //########################################

    public function execute()
    {
        $warningMessages = [];

        $Processing = $this->activeRecordFactory->getObject('Lock\Item')->getCollection()
            ->addFieldToFilter('nick', ['like' => 'synchronization_amazon%'])
            ->getSize();

        if ($Processing > 0) {
            $warningMessages[] = $this->__(
                'Data has been sent on Amazon. It is being processed now. You can continue working with M2E Pro.'
            );
        }

        $this->setJsonContent([
            'messages' => $warningMessages
        ]);

        return $this->getResponse();
    }

    //########################################
}
