<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Magento\Product;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Magento\Product\DetectDirectlyDeleted
 */
class DetectDirectlyDeleted extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'magento/product/detect_directly_deleted';

    protected $magentoProductCollectionFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo
    ) {

        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        parent::__construct(
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );
    }

    //########################################

    protected function performActions()
    {
        $this->deleteListingsProducts();
        $this->unmapListingsOther();
        $this->deleteItems();
    }

    //########################################

    protected function deleteListingsProducts()
    {
        $collection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns('product_id');
        $collection->getSelect()->distinct(true);

        $entityTableName = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('catalog_product_entity');

        $collection->getSelect()->joinLeft(
            ['cpe'=>$entityTableName],
            '(cpe.entity_id = `main_table`.product_id)',
            ['entity_id']
        );

        $collection->getSelect()->where('cpe.entity_id IS NULL');
        $collection->getSelect()->limit(100);

        $tempProductsIds = [];
        $rows = $collection->toArray();

        foreach ($rows['items'] as $row) {
            if (in_array((int)$row['product_id'], $tempProductsIds)) {
                continue;
            }

            $tempProductsIds[] = (int)$row['product_id'];

            $this->activeRecordFactory->getObject('Listing')->removeDeletedProduct((int)$row['product_id']);
        }
    }

    protected function unmapListingsOther()
    {
        $collection = $this->activeRecordFactory->getObject('Listing\Other')->getCollection();

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns('product_id');
        $collection->getSelect()->distinct(true);
        $collection->getSelect()->where('product_id IS NOT NULL');

        $entityTableName = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('catalog_product_entity');

        $collection->getSelect()->joinLeft(
            ['cpe'=>$entityTableName],
            '(cpe.entity_id = `main_table`.product_id)',
            ['entity_id']
        );

        $collection->getSelect()->where('cpe.entity_id IS NULL');
        $collection->getSelect()->limit(100);

        $tempProductsIds = [];
        $rows = $collection->toArray();

        foreach ($rows['items'] as $row) {
            if (in_array((int)$row['product_id'], $tempProductsIds)) {
                continue;
            }

            $tempProductsIds[] = (int)$row['product_id'];

            $this->activeRecordFactory->getObject('Listing\Other')->unmapDeletedProduct((int)$row['product_id']);
        }
    }

    protected function deleteItems()
    {
        foreach ($this->getHelper('Component')->getComponents() as $component) {
            $upperCasedComponent = ucfirst($component);
            $model = $this->activeRecordFactory->getObject("{$upperCasedComponent}\Item");

            if (!$model) {
                continue;
            }

            $collection = $model->getCollection();

            $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $collection->getSelect()->columns('product_id');
            $collection->getSelect()->distinct(true);
            $collection->getSelect()->where('product_id IS NOT NULL');

            $entityTableName = $this->getHelper('Module_Database_Structure')
                ->getTableNameWithPrefix('catalog_product_entity');

            $collection->getSelect()->joinLeft(
                ['cpe'=>$entityTableName],
                '(cpe.entity_id = `main_table`.product_id)',
                ['entity_id']
            );

            $collection->getSelect()->where('cpe.entity_id IS NULL');
            $collection->getSelect()->limit(100);

            $tempProductsIds = [];
            $rows = $collection->toArray();

            foreach ($rows['items'] as $row) {
                if (in_array((int)$row['product_id'], $tempProductsIds)) {
                    continue;
                }

                $tempProductsIds[] = (int)$row['product_id'];

                $this->modelFactory->getObject('Item')->removeDeletedProduct((int)$row['product_id'], $component);
            }
        }
    }

    //########################################
}
