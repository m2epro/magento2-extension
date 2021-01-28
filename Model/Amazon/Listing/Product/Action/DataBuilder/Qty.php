<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder;

use \Ess\M2ePro\Model\Magento\Product as MagentoProduct;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder\Qty
 */
class Qty extends AbstractModel
{
    const FULFILLMENT_MODE_AFN = 'AFN';
    const FULFILLMENT_MODE_MFN = 'MFN';

    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getBuilderData()
    {
        if (!empty($this->params['switch_to']) && $this->params['switch_to'] === self::FULFILLMENT_MODE_AFN) {
            return ['switch_to' => self::FULFILLMENT_MODE_AFN];
        }

        if (!isset($this->validatorsData['qty'])) {
            $this->cachedData['qty'] = $this->getAmazonListingProduct()->getQty();
        }

        $data = ['qty' => $this->cachedData['qty']];

        $this->checkQtyWarnings();

        if (!isset($this->validatorsData['handling_time'])) {
            $handlingTime = $this->getAmazonListingProduct()
                ->getListingSource()
                ->getHandlingTime();
            $this->cachedData['handling_time'] = $handlingTime;
        }

        if (!isset($this->validatorsData['restock_date'])) {
            $restockDate = $this->getAmazonListingProduct()->getListingSource()->getRestockDate();
            $this->cachedData['restock_date'] = $restockDate;
        }

        if (!empty($this->cachedData['handling_time'])) {
            $data['handling_time'] = $this->cachedData['handling_time'];
        }

        if (!empty($this->cachedData['restock_date'])) {
            $data['restock_date'] = $this->cachedData['restock_date'];
        }

        if (!empty($this->params['switch_to']) && $this->params['switch_to'] === self::FULFILLMENT_MODE_MFN) {
            $data['switch_to'] = self::FULFILLMENT_MODE_MFN;
        }

        $isRemoteFulfillmentProgram = $this->getAmazonAccount()->isRemoteFulfillmentProgramEnabled();
        if (!empty($isRemoteFulfillmentProgram)) {
            $data['remote_fulfillment_program'] = true;
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
            $this->addWarningMessage(
                'During the Quantity Calculation the Settings in the "Manage Stock No" ' .
                'field were taken into consideration.'
            );
        }

        if ($type === MagentoProduct::FORCING_QTY_TYPE_BACKORDERS) {
            $this->addWarningMessage(
                'During the Quantity Calculation the Settings in the "Backorders" ' .
                'field were taken into consideration.'
            );
        }
    }

    //########################################
}
