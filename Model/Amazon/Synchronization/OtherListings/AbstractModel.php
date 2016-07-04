<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\OtherListings;

abstract class AbstractModel extends \Ess\M2ePro\Model\Amazon\Synchronization\AbstractModel
{
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($amazonFactory, $activeRecordFactory, $helperFactory, $modelFactory);
    }

    //########################################

    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::OTHER_LISTINGS;
    }

    protected function processTask($taskPath)
    {
        return parent::processTask('OtherListings\\'.$taskPath);
    }

    //########################################
}