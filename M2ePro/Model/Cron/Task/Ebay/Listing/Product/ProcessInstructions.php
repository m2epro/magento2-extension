<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product\ProcessInstructions
 */
class ProcessInstructions extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'ebay/listing/product/process_instructions';

    //####################################

    protected function performActions()
    {
        $processor = $this->modelFactory->getObject('Listing_Product_Instruction_Processor');
        $processor->setComponent(\Ess\M2ePro\Helper\Component\Ebay::NICK);
        $processor->setMaxListingsProductsCount(
            (int)$this->getHelper('Module')->getConfig()->getGroupValue(
                '/ebay/listing/product/instructions/cron/',
                'listings_products_per_one_time'
            )
        );
        $processor->registerHandler(
            $this->modelFactory->getObject('Ebay_Listing_Product_Instruction_AutoActions_Handler')
        );
        $processor->registerHandler(
            $this->modelFactory->getObject('Ebay_Listing_Product_Instruction_SynchronizationTemplate_Handler')
        );

        if ($this->getHelper('Component_Ebay_PickupStore')->isFeatureEnabled()) {
            $processor->registerHandler(
                $this->modelFactory->getObject('Ebay_Listing_Product_Instruction_PickupStore_Handler')
            );
        }

        $processor->process();
    }

    //########################################
}
