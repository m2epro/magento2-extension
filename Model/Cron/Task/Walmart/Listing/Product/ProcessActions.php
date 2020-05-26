<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\ProcessActions
 */
class ProcessActions extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'walmart/listing/product/process_actions';

    //####################################

    protected function performActions()
    {
        $actionsProcessor = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Processor');
        $actionsProcessor->process();
    }

    //####################################
}
