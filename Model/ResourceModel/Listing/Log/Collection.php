<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing\Log;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Listing\Log\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    //########################################

    /** @var \Ess\M2ePro\Model\ResourceModel\Account */
    private $accountResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Marketplace */
    private $marketplaceResource;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Account $accountResource,
        \Ess\M2ePro\Model\ResourceModel\Marketplace $marketplaceResource,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        ?\Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        ?\Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct(
            $helperFactory,
            $activeRecordFactory,
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource
        );
        $this->accountResource = $accountResource;
        $this->marketplaceResource = $marketplaceResource;
    }

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Listing\Log::class,
            \Ess\M2ePro\Model\ResourceModel\Listing\Log::class
        );
    }

    //########################################

    /**
     * GroupBy fix
     */
    public function getSelectCountSql()
    {
        $this->_renderFilters();

        $originSelect = clone $this->getSelect();
        $originSelect->reset(\Magento\Framework\DB\Select::ORDER);
        $originSelect->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $originSelect->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $originSelect->reset(\Magento\Framework\DB\Select::COLUMNS);
        $originSelect->columns(['*']);

        $countSelect = clone $originSelect;
        $countSelect->reset();
        $countSelect->from($originSelect, null);
        $countSelect->columns(new \Zend_Db_Expr('COUNT(*)'));

        return $countSelect;
    }

    public function skipIncorrectAccounts(): void
    {
        $this->getSelect()->joinInner(
            ['account' => $this->accountResource->getMainTable()],
            'main_table.account_id = account.id',
            []
        );
    }

    public function skipIncorrectMarketplaces(): void
    {
        $this->getSelect()->joinInner(
            ['marketplace' => $this->marketplaceResource->getMainTable()],
            'main_table.marketplace_id = marketplace.id',
            []
        );
    }

    //########################################
}
