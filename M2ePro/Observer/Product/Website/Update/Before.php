<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Observer\Product\Website\Update;

/**
 * Class \Ess\M2ePro\Observer\Product\Website\Update\Before
 */
class Before extends \Ess\M2ePro\Observer\AbstractModel
{
    /** @var \Magento\Store\Model\ResourceModel\Store\CollectionFactory */
    protected $storeCollectionFactory;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    //########################################

    public function __construct(
        \Magento\Store\Model\ResourceModel\Store\CollectionFactory $storeFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->storeCollectionFactory = $storeFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($helperFactory, $activeRecordFactory, $modelFactory);
    }

    //########################################

    public function process()
    {
        $productIds = $this->getEventObserver()->getData('product_ids');
        $websiteIds = $this->getEventObserver()->getData('website_ids');
        $action = $this->getAction($this->getEventObserver()->getData('action'));

        if (empty($productIds) || empty($websiteIds) || empty($action)) {
            return;
        }

        /** @var \Magento\Store\Model\ResourceModel\Store\Collection $storesCollection */
        $storesCollection = $this->storeCollectionFactory->create();
        $storesCollection->addFieldToFilter('website_id', ['in' => $websiteIds]);

        $storeIds = $storesCollection->getColumnValues('store_id');

        /** @var $listings \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection */
        $listings = $this->activeRecordFactory->getObject('Listing')->getCollection();
        $listings->getSelect()->where('store_id IN (?)', $storeIds);
        $listings->getSelect()->where(
            'auto_website_adding_mode != ' . \Ess\M2ePro\Model\Listing::ADDING_MODE_NONE . ' OR ' .
            'auto_website_deleting_mode != ' . \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE
        );

        if ($listings->getSize() == 0) {
            return;
        }

        $select = $this->resourceConnection->getConnection()
            ->select()
            ->from($this->getHelper('Module_Database_Structure')
                            ->getTableNameWithPrefix('catalog_product_website'))
            ->where('product_id IN (?)', $productIds);

        $currentProductWebsites = $this->resourceConnection->getConnection()->fetchAll($select);

        $websiteUpdates = $this->activeRecordFactory->getObject('Magento_Product_Websites_Update')->getCollection()
            ->addFieldToFilter('product_id', ['in' => $productIds])
            ->getItems();

        $addWebsiteUpdates = [];
        $deleteWebsiteUpdates = [];

        foreach ($productIds as $productId) {
            foreach ($websiteIds as $websiteId) {
                $websiteUpdate = false;
                foreach ($websiteUpdates as $wUpdate) {
                    /** @var \Ess\M2ePro\Model\Magento\Product\Websites\Update $wUpdate */
                    if ($wUpdate->getProductId() == $productId && $wUpdate->getWebsiteId() == $websiteId) {
                        $websiteUpdate = $wUpdate;
                        break;
                    }
                }

                $currentProductWebsite = false;
                foreach ($currentProductWebsites as $productWebsite) {
                    if ($productWebsite['product_id'] == $productId && $productWebsite['website_id'] == $websiteId) {
                        $currentProductWebsite = $productWebsite;
                    }
                }

                if ($action == \Ess\M2ePro\Model\Magento\Product\Websites\Update::ACTION_ADD) {
                    $websiteCheckByAction = $currentProductWebsite === false;
                } else {
                    $websiteCheckByAction = $currentProductWebsite !== false;
                }

                if (!$websiteUpdate && $websiteCheckByAction) {
                    $addWebsiteUpdates[] = [
                        'product_id'  => $productId,
                        'action'      => $action,
                        'website_id'  => $websiteId,
                        'create_date' => $this->getHelper('Data')->getCurrentGmtDate()
                    ];
                    continue;
                }

                if ($websiteUpdate &&
                    $websiteUpdate->getAction() != $action &&
                    $websiteCheckByAction
                ) {
                    $deleteWebsiteUpdates[] = $websiteUpdate->getId();
                    continue;
                }
            }
        }

        $table = $this->getHelper('Module_Database_Structure')
            ->getTableNameWithPrefix('m2epro_magento_product_websites_update');

        if (!empty($deleteWebsiteUpdates)) {
            $this->resourceConnection->getConnection()
                ->delete(
                    $table,
                    '`id` IN (' . implode(',', $deleteWebsiteUpdates) . ')'
                );
        }

        if (!empty($addWebsiteUpdates)) {
            $this->resourceConnection->getConnection()
                ->insertMultiple(
                    $table,
                    $addWebsiteUpdates
                );
        }
    }

    //########################################

    protected function getAction($action)
    {
        switch ($action) {
            case 'add':
                return \Ess\M2ePro\Model\Magento\Product\Websites\Update::ACTION_ADD;

            case 'remove':
                return \Ess\M2ePro\Model\Magento\Product\Websites\Update::ACTION_REMOVE;
        }

        return null;
    }

    //########################################
}
