<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Issue\Notification;

use Ess\M2ePro\Model\Issue\DataObject;

interface ChannelInterface
{
    /**
     * @param DataObject $message
     *
     * @return void
     */
    public function addMessage(DataObject $message): void;
}
