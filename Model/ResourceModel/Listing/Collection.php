<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Listing;

class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\Parent\AbstractModel
{
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $moduleDatabaseStructure;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Database\Structure $moduleDatabaseStructure,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null,
        $childMode = null
    ) {
        parent::__construct(
            $helperFactory,
            $activeRecordFactory,
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $connection,
            $resource,
            $childMode
        );

        $this->moduleDatabaseStructure = $moduleDatabaseStructure;
    }

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Listing::class,
            \Ess\M2ePro\Model\ResourceModel\Listing::class
        );
    }

    public function addProductsTotalCount(): Collection
    {
        $listingProductTable = $this->moduleDatabaseStructure->getTableNameWithPrefix('m2epro_listing_product');

        $sql = <<<SQL
SELECT listing_id, COUNT(id) AS products_total_count
FROM `{$listingProductTable}`
GROUP BY listing_id
SQL;

        $this->getSelect()->joinLeft(
            new \Zend_Db_Expr('(' . $sql . ')'),
            'main_table.id=t.listing_id',
            [
                'products_total_count' => 'products_total_count',
            ]
        );

        return $this;
    }
}
