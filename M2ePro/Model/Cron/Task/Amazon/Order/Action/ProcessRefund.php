<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Action;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Action\ProcessRefund
 */
class ProcessRefund extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/order/action/process_refund';

    /**
     * @var int (in seconds)
     */
    protected $interval = 18000;

    //####################################

    public function performActions()
    {
        $actionsProcessor = $this->modelFactory->getObject(
            'Amazon_Order_Action_Processor',
            [
                'params' => ['action_type' => \Ess\M2ePro\Model\Amazon\Order\Action\Processing::ACTION_TYPE_REFUND]
            ]
        );
        $actionsProcessor->process();
    }

    //####################################
}
