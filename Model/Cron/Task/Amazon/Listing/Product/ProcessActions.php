<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\ProcessActions
 */
class ProcessActions extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/listing/product/process_actions';

    //####################################

    protected function performActions()
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Processor $actionsProcessor */
        $actionsProcessor = $this->modelFactory->getObject('Amazon_Listing_Product_Action_Processor');
        $actionsProcessor->process();
    }

    //####################################
}
