<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Product;

class ProcessInstructions extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'ebay/listing/product/process_instructions';

    /** @var \Ess\M2ePro\Helper\Component\Ebay\PickupStore */
    private $componentEbayPickupStore;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\PickupStore $componentEbayPickupStore,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        parent::__construct(
            $helperData,
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );

        $this->componentEbayPickupStore = $componentEbayPickupStore;
    }

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

        if ($this->componentEbayPickupStore->isFeatureEnabled()) {
            $processor->registerHandler(
                $this->modelFactory->getObject('Ebay_Listing_Product_Instruction_PickupStore_Handler')
            );
        }

        $processor->process();
    }

    //########################################
}
