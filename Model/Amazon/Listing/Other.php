<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing;

/**
 * @method \Ess\M2ePro\Model\Listing\Other getParentObject()
 */
class Other extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    const EMPTY_TITLE_PLACEHOLDER = '--';

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init('Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Other');
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
    public function getGeneralId()
    {
        return $this->getData('general_id');
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

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isAfnChannel()
    {
        return (int)$this->getData('is_afn_channel') ==
            \Ess\M2ePro\Model\Amazon\Listing\Product::IS_AFN_CHANNEL_YES;
    }

    /**
     * @return bool
     */
    public function isIsbnGeneralId()
    {
        return (int)$this->getData('is_isbn_general_id') ==
            \Ess\M2ePro\Model\Amazon\Listing\Product::IS_ISBN_GENERAL_ID_YES;
    }

    // ---------------------------------------

    /**
     * @return bool
     */
    public function isRepricing()
    {
        return (bool)$this->getData('is_repricing');
    }

    /**
     * @return bool
     */
    public function isRepricingDisabled()
    {
        return (bool)$this->getData('is_repricing_disabled');
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
        $dataForAdd = array(
            'account_id' => $this->getParentObject()->getAccountId(),
            'marketplace_id' => $this->getParentObject()->getMarketplaceId(),
            'sku' => $this->getSku(),
            'product_id' => $this->getParentObject()->getProductId(),
            'store_id' => $this->getRelatedStoreId()
        );

        $this->activeRecordFactory->getObject('Amazon\Item')->setData($dataForAdd)->save();
    }

    public function beforeUnmapProduct()
    {
        $connection = $this->getResource()->getConnection();

        $itemTable = $this->activeRecordFactory->getObject('Amazon\Item')->getResource()->getMainTable();
        $productTable = $this->activeRecordFactory->getObject('Amazon\Listing\Product')->getResource()->getMainTable();

        $existedRelation = $connection->select()
            ->from(array('ai' => $itemTable),
                   array())
            ->join(array('alp' => $productTable),
                   '(`alp`.`sku` = `ai`.`sku`)', array('alp.listing_product_id'))
            ->where('`ai`.`sku` = ?', $this->getSku())
            ->where('`ai`.`account_id` = ?', $this->getParentObject()->getAccountId())
            ->where('`ai`.`marketplace_id` = ?', $this->getParentObject()->getMarketplaceId())
            ->query()
            ->fetchColumn();

        if ($existedRelation) {
            return;
        }

        $connection->delete(
            $itemTable,
            array(
                '`account_id` = ?' => $this->getParentObject()->getAccountId(),
                '`marketplace_id` = ?' => $this->getParentObject()->getMarketplaceId(),
                '`sku` = ?' => $this->getSku(),
                '`product_id` = ?' => $this->getParentObject()->getProductId()
            )
        );
    }

    //########################################
}