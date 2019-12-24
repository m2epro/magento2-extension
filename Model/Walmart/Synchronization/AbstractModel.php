<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Synchronization;

/**
 * Class \Ess\M2ePro\Model\Walmart\Synchronization\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent
{
    protected $resourceConnection;
    protected $walmartFactory;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->walmartFactory = $walmartFactory;
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory);
    }

    //########################################

    protected function getComponent()
    {
        return \Ess\M2ePro\Helper\Component\Walmart::NICK;
    }

    //########################################
}
