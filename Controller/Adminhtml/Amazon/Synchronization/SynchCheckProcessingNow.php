<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Settings;

class SynchCheckProcessingNow extends Settings
{
    //########################################

    public function execute()
    {
        $warningMessages = array();

        $Processing = $this->activeRecordFactory->getObject('Lock\Item')->getCollection()
            ->addFieldToFilter('nick', array('like' => 'synchronization_amazon%'))
            ->getSize();

        if ($Processing > 0) {
            $warningMessages[] = $this->__(
                'Data has been sent on Amazon. It is being processed now. You can continue working with M2E Pro.'
            );
        }

        $this->setJsonContent(array(
            'messages' => $warningMessages
        ));

        return $this->getResponse();
    }

    //########################################
}