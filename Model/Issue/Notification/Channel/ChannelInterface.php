<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Issue\Notification\Channel;

/**
 * Class \Ess\M2ePro\Model\Issue\Notification\Channel\ChannelInterface
 */
interface ChannelInterface
{
    //########################################

    public function addMessage(\Ess\M2ePro\Model\Issue\DataObject $message);

    //########################################
}
