<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Walmart\Listing\Product\ProcessInstructions
 */
class ProcessInstructions extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'walmart/listing/product/process_instructions';

    //####################################

    protected function performActions()
    {
        $processor = $this->modelFactory->getObject('Listing_Product_Instruction_Processor');
        $processor->setComponent(\Ess\M2ePro\Helper\Component\Walmart::NICK);
        $processor->setMaxListingsProductsCount(
            (int)$this->getHelper('Module')->getConfig()->getGroupValue(
                '/walmart/listing/product/instructions/cron/',
                'listings_products_per_one_time'
            )
        );
        $processor->registerHandler(
            $this->modelFactory->getObject('Walmart_Listing_Product_Instruction_AutoActions_Handler')
        );
        $processor->registerHandler(
            $this->modelFactory->getObject('Walmart_Listing_Product_Instruction_SynchronizationTemplate_Handler')
        );

        $processor->process();
    }

    //########################################
}
