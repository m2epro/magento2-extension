<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Moving;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Listing\Moving\PrepareMoveToListing
 */
class PrepareMoveToListing extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    //########################################

    public function execute()
    {
        $dbHelper = $this->getHelper('Module_Database_Structure');
        $sessionHelper = $this->getHelper('Data\Session');
        $componentMode = $this->getRequest()->getParam('componentMode');
        $sessionKey = $componentMode . '_' . \Ess\M2ePro\Helper\View::MOVING_LISTING_PRODUCTS_SELECTED_SESSION_KEY;

        if ((bool)$this->getRequest()->getParam('is_first_part')) {
            $sessionHelper->removeValue($sessionKey);
        }

        $selectedProducts = [];
        if ($sessionValue = $sessionHelper->getValue($sessionKey)) {
            $selectedProducts = $sessionValue;
        }

        $selectedProductsPart = $this->getRequest()->getParam('products_part');
        $selectedProductsPart = explode(',', $selectedProductsPart);

        $selectedProducts = array_merge($selectedProducts, $selectedProductsPart);
        $sessionHelper->setValue($sessionKey, $selectedProducts);

        if (!(bool)$this->getRequest()->getParam('is_last_part')) {
            $this->setJsonContent(['result' => true]);
            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->parentFactory
            ->getObject($componentMode, 'Listing\Product')
            ->getCollection();

        $listingProductCollection->addFieldToFilter('main_table.id', ['in' => $selectedProducts]);
        $row = $listingProductCollection
            ->getSelect()
            ->join(
                ['listing' => $dbHelper->getTableNameWithPrefix('m2epro_listing')],
                '`main_table`.`listing_id` = `listing`.`id`'
            )
            ->join(
                ['cpe' => $dbHelper->getTableNameWithPrefix('catalog_product_entity')],
                '`main_table`.`product_id` = `cpe`.`entity_id`'
            )
            ->group(['listing.account_id', 'listing.marketplace_id'])
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(['marketplace_id', 'account_id'], 'listing')
            ->query()
            ->fetch();

        $this->setJsonContent([
            'result'        => true,
            'accountId'     => (int)$row['account_id'],
            'marketplaceId' => (int)$row['marketplace_id']
        ]);
        return $this->getResult();
    }

    //########################################
}
