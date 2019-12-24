<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Synchronization\Marketplaces;

/**
 * Class \Ess\M2ePro\Model\Ebay\Synchronization\Marketplaces\AbstractModel
 */
abstract class AbstractModel extends \Ess\M2ePro\Model\Ebay\Synchronization\AbstractModel
{
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->resourceConnection = $resourceConnection;
        parent::__construct($ebayFactory, $activeRecordFactory, $helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return string
     */
    protected function getType()
    {
        return \Ess\M2ePro\Model\Synchronization\Task\AbstractComponent::MARKETPLACES;
    }

    protected function processTask($taskPath)
    {
        return parent::processTask('Marketplaces\\'.$taskPath);
    }

    //########################################
}
