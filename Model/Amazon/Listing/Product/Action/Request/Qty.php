<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request;

use \Ess\M2ePro\Model\Magento\Product as MagentoProduct;

class Qty extends \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Request\AbstractModel
{
    const FULFILLMENT_MODE_AFN = 'AFN';
    const FULFILLMENT_MODE_MFN = 'MFN';

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        if (!$this->getConfigurator()->isQtyAllowed()) {
            return array();
        }

        $params = $this->getParams();
        if (!empty($params['switch_to']) && $params['switch_to'] === self::FULFILLMENT_MODE_AFN) {
            return array(
                'switch_to' => self::FULFILLMENT_MODE_AFN
            );
        }

        if (!isset($this->validatorsData['qty'])) {
            $this->validatorsData['qty'] = $this->getAmazonListingProduct()->getQty();
        }

        $data = array(
            'qty' => $this->validatorsData['qty'],
        );

        $this->checkQtyWarnings();

        if (!isset($this->validatorsData['handling_time'])) {
            $handlingTime = $this->getAmazonListingProduct()->getListingSource()->getHandlingTime();
            $this->validatorsData['handling_time'] = $handlingTime;
        }

        if (!isset($this->validatorsData['restock_date'])) {
            $restockDate = $this->getAmazonListingProduct()->getListingSource()->getRestockDate();
            $this->validatorsData['restock_date'] = $restockDate;
        }

        if (!empty($this->validatorsData['handling_time'])) {
            $data['handling_time'] = $this->validatorsData['handling_time'];
        }

        if (!empty($this->validatorsData['restock_date'])) {
            $data['restock_date'] = $this->validatorsData['restock_date'];
        }

        if (!empty($params['switch_to']) && $params['switch_to'] === self::FULFILLMENT_MODE_MFN) {
            $data['switch_to'] = self::FULFILLMENT_MODE_MFN;
        }

        return $data;
    }

    //########################################

    public function checkQtyWarnings()
    {
        $qtyMode = $this->getAmazonListing()->getAmazonSellingFormatTemplate()->getQtyMode();
        if ($qtyMode == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT_FIXED ||
            $qtyMode == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT) {

            $listingProductId = $this->getListingProduct()->getId();
            $productId = $this->getAmazonListingProduct()->getActualMagentoProduct()->getProductId();
            $storeId = $this->getListing()->getStoreId();

            if (!empty(MagentoProduct::$statistics[$listingProductId][$productId][$storeId]['qty'])) {

                $qtys = MagentoProduct::$statistics[$listingProductId][$productId][$storeId]['qty'];
                foreach ($qtys as $type => $override) {
                    $this->addQtyWarnings($type);
                }
            }
        }
    }

    public function addQtyWarnings($type)
    {
        if ($type === MagentoProduct::FORCING_QTY_TYPE_MANAGE_STOCK_NO) {
            // M2ePro\TRANSLATIONS
            // During the Quantity Calculation the Settings in the "Manage Stock No" field were taken into consideration.
            $this->addWarningMessage('During the Quantity Calculation the Settings in the "Manage Stock No" '.
                'field were taken into consideration.');
        }

        if ($type === MagentoProduct::FORCING_QTY_TYPE_BACKORDERS) {
            // M2ePro\TRANSLATIONS
            // During the Quantity Calculation the Settings in the "Backorders" field were taken into consideration.
            $this->addWarningMessage('During the Quantity Calculation the Settings in the "Backorders" '.
                'field were taken into consideration.');
        }
    }

    //########################################
}