<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Magento\Category;

use Magento\Catalog\Api\Data\CategoryAttributeInterface;

class Collection extends \Magento\Catalog\Model\ResourceModel\Category\Collection
{
    /** @var \Ess\M2ePro\Helper\Factory */
    protected $helperFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Eav\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null
    ) {
        $this->helperFactory = $helperFactory;

        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $eavConfig,
            $resource,
            $eavEntityFactory,
            $resourceHelper,
            $universalFactory,
            $storeManager,
            $connection
        );
    }

    //########################################

    /**
     * Compatibility with Magento Enterprise (Staging modules) - entity_id column issue
     */
    public function joinTable($table, $bind, $fields = null, $cond = null, $joinType = 'inner')
    {
        /** @var \Ess\M2ePro\Helper\Magento\Staging $helper */
        $helper = $this->helperFactory->getObject('Magento\Staging');

        if ($helper->isInstalled() &&
            $helper->isStagedTable($table, CategoryAttributeInterface::ENTITY_TYPE_CODE) &&
            strpos($bind, 'entity_id') !== false) {

            $bind = str_replace(
                'entity_id',
                $helper->getTableLinkField(CategoryAttributeInterface::ENTITY_TYPE_CODE),
                $bind
            );
        }

        return parent::joinTable($table, $bind, $fields, $cond, $joinType);
    }

    /**
     * Compatibility with Magento Enterprise (Staging modules) - entity_id column issue
     */
    public function joinAttribute($alias, $attribute, $bind, $filter = NULL, $joinType = 'inner', $storeId = NULL)
    {
        /** @var \Ess\M2ePro\Helper\Magento\Staging $helper */
        $helper = $this->helperFactory->getObject('Magento\Staging');

        if ($helper->isInstalled() && is_string($attribute) && is_string($bind)) {
            $attrArr = explode('/', $attribute);
            if (CategoryAttributeInterface::ENTITY_TYPE_CODE == $attrArr[0] && $bind == 'entity_id') {
                $bind = $helper->getTableLinkField(CategoryAttributeInterface::ENTITY_TYPE_CODE);
            }
        }
        return parent::joinAttribute($alias, $attribute, $bind, $filter, $joinType, $storeId);
    }

    //########################################
}