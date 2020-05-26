<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\ProcessListActions
 */
class ProcessListActions extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'walmart/listing/product/process_list_actions';

    //####################################

    protected function performActions()
    {
        $actionsProcessor = $this->modelFactory->getObject('Walmart_Listing_Product_Action_ListAction_Processor');
        $actionsProcessor->process();
    }

    //####################################
}
