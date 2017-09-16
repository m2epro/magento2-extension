<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization\GlobalTask\MagentoProducts;

class DeletedProducts extends AbstractModel
{
    private $itemModel;
    private $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Item $itemModel,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->itemModel = $itemModel;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($activeRecordFactory, $helperFactory, $modelFactory);
    }

    //########################################

    /**
     * @return string
     */
    protected function getNick()
    {
        return '/deleted_products/';
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return 'Remove Deleted Products';
    }

    // ---------------------------------------

    /**
     * @return int
     */
    protected function getPercentsStart()
    {
        return 60;
    }

    /**
     * @return int
     */
    protected function getPercentsEnd()
    {
        return 70;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    protected function intervalIsEnabled()
    {
        return true;
    }

    //########################################

    protected function performActions()
    {
        $this->deleteListingsProducts();
        $this->unmapListingsOther();
        $this->deleteItems();
    }

    //########################################

    private function deleteListingsProducts()
    {
        $collection = $this->activeRecordFactory->getObject('Listing\Product')->getCollection();

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns('product_id');
        $collection->getSelect()->distinct(true);

        $entityTableName = $this->resourceConnection->getTableName('catalog_product_entity');

        $collection->getSelect()->joinLeft(
            array('cpe'=>$entityTableName), '(cpe.entity_id = `main_table`.product_id)',array('entity_id')
        );

        $collection->getSelect()->where('cpe.entity_id IS NULL');

        $tempProductsIds = array();
        $rows = $collection->toArray();

        foreach ($rows['items'] as $row) {

            if (in_array((int)$row['product_id'],$tempProductsIds)) {
                continue;
            }

            $tempProductsIds[] = (int)$row['product_id'];

            $this->activeRecordFactory->getObject('Listing')->removeDeletedProduct((int)$row['product_id']);
            $this->activeRecordFactory->getObject('ProductChange')->removeDeletedProduct((int)$row['product_id']);
        }
    }

    private function unmapListingsOther()
    {
        $collection = $this->activeRecordFactory->getObject('Listing\Other')->getCollection();

        $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
        $collection->getSelect()->columns('product_id');
        $collection->getSelect()->distinct(true);
        $collection->getSelect()->where('product_id IS NOT NULL');

        $entityTableName = $this->resourceConnection->getTableName('catalog_product_entity');

        $collection->getSelect()->joinLeft(
            array('cpe'=>$entityTableName), '(cpe.entity_id = `main_table`.product_id)',array('entity_id')
        );

        $collection->getSelect()->where('cpe.entity_id IS NULL');

        $tempProductsIds = array();
        $rows = $collection->toArray();

        foreach ($rows['items'] as $row) {

            if (in_array((int)$row['product_id'],$tempProductsIds)) {
                continue;
            }

            $tempProductsIds[] = (int)$row['product_id'];

            $this->activeRecordFactory->getObject('Listing\Other')->unmapDeletedProduct((int)$row['product_id']);
            $this->activeRecordFactory->getObject('ProductChange')->removeDeletedProduct((int)$row['product_id']);
        }
    }

    private function deleteItems()
    {
        foreach ($this->getHelper('Component')->getComponents() as $component) {

            $upperCasedComponent = ucfirst($component);
            $model = $this->activeRecordFactory->getObject("{$upperCasedComponent}\\Item");

            if (!$model) {
                continue;
            }

            $collection = $model->getCollection();

            $collection->getSelect()->reset(\Zend_Db_Select::COLUMNS);
            $collection->getSelect()->columns('product_id');
            $collection->getSelect()->distinct(true);
            $collection->getSelect()->where('product_id IS NOT NULL');

            $entityTableName = $this->resourceConnection->getTableName('catalog_product_entity');

            $collection->getSelect()->joinLeft(
                array('cpe'=>$entityTableName), '(cpe.entity_id = `main_table`.product_id)', array('entity_id')
            );

            $collection->getSelect()->where('cpe.entity_id IS NULL');

            $tempProductsIds = array();
            $rows = $collection->toArray();

            foreach ($rows['items'] as $row) {

                if (in_array((int)$row['product_id'],$tempProductsIds)) {
                    continue;
                }

                $tempProductsIds[] = (int)$row['product_id'];

                $this->itemModel->removeDeletedProduct((int)$row['product_id'], $component);
                $this->activeRecordFactory->getObject('ProductChange')->removeDeletedProduct((int)$row['product_id']);
            }
        }
    }

    //########################################
}