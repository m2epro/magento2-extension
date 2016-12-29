<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento\Product;

class Status extends \Ess\M2ePro\Model\AbstractModel
{
    protected $resourceModel;
    protected $productResource;
    protected $magentoProductCollectionFactory;

    protected $_productAttributes  = array();

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceModel,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->resourceModel = $resourceModel;
        $this->productResource = $productResource;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    protected function _getProductAttribute($attribute)
    {
        if (empty($this->_productAttributes[$attribute])) {
            $this->_productAttributes[$attribute] = $this->productResource->getAttribute($attribute);
        }
        return $this->_productAttributes[$attribute];
    }

    protected function _getReadAdapter()
    {
        return $this->resourceModel->getConnection();
    }

    //########################################

    public function getProductStatus($productIds, $storeId = null)
    {
        if (!is_array($productIds)) {
            $productIds = array($productIds);
        }

        /* @var $collection \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection */
        $collection = $this->magentoProductCollectionFactory->create();
        $collection->addFieldToFilter([
            ['attribute' => 'entity_id', 'in' => $productIds]
        ]);
        $collection->joinAttribute(
            'status', 'catalog_product/status', 'entity_id', NULL, 'inner', (int)$storeId
        );

        $rows = [];
        $queryStmt = $collection->getSelect()->query();

        while ($row = $queryStmt->fetch()) {
            $rows[$row['entity_id']] = $row['status'];
        }

        $statuses = array();

        foreach ($productIds as $productId) {
            if (isset($rows[$productId])) {
                $statuses[$productId] = $rows[$productId];
            } else {
                $statuses[$productId] = -1;
            }
        }

        return $statuses;
    }

    //########################################
}