<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection;

abstract class AbstractCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $helperFactory;
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    )
    {
        $this->helperFactory = $helperFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
    }

    //########################################

    protected function _toOptionArray($valueField = 'id', $labelField = 'title', $additional = array())
    {
        return parent::_toOptionArray($valueField, $labelField, $additional);
    }

    protected function _toOptionHash($valueField = 'id', $labelField = 'title')
    {
        return parent::_toOptionHash($valueField, $labelField);
    }

    //########################################

    /**
     * @param $helperName
     * @param array $arguments
     * @return \Magento\Framework\App\Helper\AbstractHelper
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getHelper($helperName, array $arguments = [])
    {
        return $this->helperFactory->getObject($helperName, $arguments);
    }

    //########################################
}