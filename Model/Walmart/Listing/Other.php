<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Listing\Other getParentObject()
 */

namespace Ess\M2ePro\Model\Walmart\Listing;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Other
 */
class Other extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Walmart\AbstractModel
{
    const EMPTY_TITLE_PLACEHOLDER = '--';

    protected $resourceConnection;

    //########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $walmartFactory,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->resourceConnection = $resourceConnection;
    }

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Walmart\Listing\Other');
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        return $this->getParentObject()->getAccount();
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        return $this->getParentObject()->getMarketplace();
    }

    /**
     * @return \Ess\M2ePro\Model\Magento\Product\Cache
     */
    public function getMagentoProduct()
    {
        return $this->getParentObject()->getMagentoProduct();
    }

    //########################################

    /**
     * @return string
     */
    public function getSku()
    {
        return $this->getData('sku');
    }

    /**
     * @return mixed
     */
    public function getGtin()
    {
        return $this->getData('gtin');
    }

    /**
     * @return mixed
     */
    public function getUpc()
    {
        return $this->getData('upc');
    }

    /**
     * @return mixed
     */
    public function getEan()
    {
        return $this->getData('ean');
    }

    /**
     * @return mixed
     */
    public function getWpid()
    {
        return $this->getData('wpid');
    }

    /**
     * @return mixed
     */
    public function getItemId()
    {
        return $this->getData('item_id');
    }

    // ---------------------------------------

    /**
     * @return string
     */
    public function getChannelUrl()
    {
        return $this->getData('channel_url');
    }

    // ---------------------------------------

    /**
     * @return string
     */
    public function getPublishStatus()
    {
        return $this->getData('publish_status');
    }

    /**
     * @return string
     */
    public function getLifecycleStatus()
    {
        return $this->getData('lifecycle_status');
    }

    /**
     * @return array
     */
    public function getStatusChangeReasons()
    {
        return $this->getSettings('status_change_reasons');
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isOnlinePriceInvalid()
    {
        return (bool)$this->getData('is_online_price_invalid');
    }

    // ---------------------------------------

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->getData('title');
    }

    // ---------------------------------------

    /**
     * @return float
     */
    public function getOnlinePrice()
    {
        return (float)$this->getData('online_price');
    }

    /**
     * @return int
     */
    public function getOnlineQty()
    {
        return (int)$this->getData('online_qty');
    }

    //########################################

    /**
     * @return mixed
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getRelatedStoreId()
    {
        return $this->getAccount()->getChildObject()->getRelatedStoreId();
    }

    //########################################

    public function afterMapProduct()
    {
        $dataForAdd = [
            'account_id'     => $this->getParentObject()->getAccountId(),
            'marketplace_id' => $this->getParentObject()->getMarketplaceId(),
            'sku'            => $this->getSku(),
            'product_id'     => $this->getParentObject()->getProductId(),
            'store_id'       => $this->getRelatedStoreId()
        ];

        $this->activeRecordFactory->getObject('Walmart\Item')->setData($dataForAdd)->save();
    }

    public function beforeUnmapProduct()
    {
        $existedRelation = $this->resourceConnection->getConnection()
            ->select()
            ->from(
                ['ai' => $this->activeRecordFactory->getObject('Walmart\Item')->getResource()->getMainTable()],
                []
            )
            ->join(
                ['alp' => $this->activeRecordFactory->getObject('Walmart_Listing_Product')
                ->getResource()->getMainTable()],
                '(`alp`.`sku` = `ai`.`sku`)',
                ['alp.listing_product_id']
            )
            ->where('`ai`.`sku` = ?', $this->getSku())
            ->where('`ai`.`account_id` = ?', $this->getParentObject()->getAccountId())
            ->where('`ai`.`marketplace_id` = ?', $this->getParentObject()->getMarketplaceId())
            ->query()
            ->fetchColumn();

        if ($existedRelation) {
            return;
        }

        $this->resourceConnection->getConnection()
            ->delete(
                $this->activeRecordFactory->getObject('Walmart\Item')->getResource()->getMainTable(),
                [
                    '`account_id` = ?'     => $this->getParentObject()->getAccountId(),
                    '`marketplace_id` = ?' => $this->getParentObject()->getMarketplaceId(),
                    '`sku` = ?'            => $this->getSku(),
                    '`product_id` = ?'     => $this->getParentObject()->getProductId()
                ]
            );
    }

    //########################################
}
