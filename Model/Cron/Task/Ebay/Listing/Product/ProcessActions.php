<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product\ProcessActions
 */
class ProcessActions extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'ebay/listing/product/process_actions';

    //####################################

    protected function performActions()
    {
        $actionsProcessor = $this->modelFactory->getObject('Ebay_Listing_Product_Action_Processor');
        $actionsProcessor->process();
    }

    //####################################
}
