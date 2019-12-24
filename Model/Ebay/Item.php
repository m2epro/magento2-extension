<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay;

use Ess\M2ePro\Model\ActiveRecord\Factory;

/**
 * Class \Ess\M2ePro\Model\Ebay\Item
 */
class Item extends \Ess\M2ePro\Model\ActiveRecord\Component\AbstractModel
{
    private $ebayParentFactory;

    /**
     * @var \Ess\M2ePro\Model\Account
     */
    private $accountModel = null;

    /**
     * @var \Ess\M2ePro\Model\Marketplace
     */
    private $marketplaceModel = null;

    /**
     * @var \Ess\M2ePro\Model\Magento\Product
     */
    protected $magentoProductModel = null;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayParentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->ebayParentFactory = $ebayParentFactory;

        parent::__construct(
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );
    }

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Ebay\Item');
    }

    //########################################

    public function delete()
    {
        $temp = parent::delete();
        $temp && $this->accountModel = null;
        $temp && $this->marketplaceModel = null;
        $temp && $this->magentoProductModel = null;
        return $temp;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        if ($this->accountModel === null) {
            $this->accountModel = $this->ebayParentFactory->getCachedObjectLoaded(
                'Account',
                $this->getAccountId()
            );
        }

        return $this->accountModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Account $instance
     */
    public function setAccount(\Ess\M2ePro\Model\Account $instance)
    {
        $this->accountModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        if ($this->marketplaceModel === null) {
            $this->marketplaceModel = $this->ebayParentFactory->getCachedObjectLoaded(
                'Marketplace',
                $this->getMarketplaceId()
            );
        }

        return $this->marketplaceModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Marketplace $instance
     */
    public function setMarketplace(\Ess\M2ePro\Model\Marketplace $instance)
    {
        $this->marketplaceModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Magento\Product
     */
    public function getMagentoProduct()
    {
        if ($this->magentoProductModel) {
            return $this->magentoProductModel;
        }

        return $this->magentoProductModel = $this->modelFactory->getObject('Magento\Product')
                ->setStoreId($this->getStoreId())
                ->setProductId($this->getProductId());
    }

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $instance
     */
    public function setMagentoProduct(\Ess\M2ePro\Model\Magento\Product $instance)
    {
        $this->magentoProductModel = $instance;
    }

    //########################################

    /**
     * @return float
     */
    public function getItemId()
    {
        return (double)$this->getData('item_id');
    }

    /**
     * @return float
     */
    public function getAccountId()
    {
        return (double)$this->getData('account_id');
    }

    /**
     * @return float
     */
    public function getMarketplaceId()
    {
        return (double)$this->getData('marketplace_id');
    }

    /**
     * @return int
     */
    public function getProductId()
    {
        return (int)$this->getData('product_id');
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return (int)$this->getData('store_id');
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getVariations()
    {
        return $this->getSettings('variations');
    }

    //########################################
}
