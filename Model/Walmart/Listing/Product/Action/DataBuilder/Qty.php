<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ss-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\Qty
 */
class Qty extends \Ess\M2ePro\Model\Walmart\Listing\Product\Action\DataBuilder\AbstractModel
{
    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        if (!isset($this->cachedData['qty'])) {
            $this->cachedData['qty'] = $this->getWalmartListingProduct()->getQty();
        }

        $data = [
            'qty' => $this->cachedData['qty'],
        ];

        $this->checkQtyWarnings();

        return $data;
    }

    //########################################

    public function checkQtyWarnings()
    {
        $qtyMode = $this->getWalmartListing()->getWalmartSellingFormatTemplate()->getQtyMode();
        if ($qtyMode == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT_FIXED ||
            $qtyMode == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT) {
            $listingProductId = $this->getListingProduct()->getId();
            $productId = $this->getWalmartListingProduct()->getActualMagentoProduct()->getProductId();
            $storeId = $this->getListing()->getStoreId();

            if (!empty(\Ess\M2ePro\Model\Magento\Product::$statistics[$listingProductId][$productId][$storeId]['qty'])
            ) {
                $qtys = \Ess\M2ePro\Model\Magento\Product::$statistics[$listingProductId][$productId][$storeId]['qty'];
                foreach ($qtys as $type => $override) {
                    $this->addQtyWarnings($type);
                }
            }
        }
    }

    public function addQtyWarnings($type)
    {
        if ($type === \Ess\M2ePro\Model\Magento\Product::FORCING_QTY_TYPE_MANAGE_STOCK_NO) {
            $this->addWarningMessage('During the Quantity Calculation the Settings in the "Manage Stock No" ' .
                'field were taken into consideration.');
        }

        if ($type === \Ess\M2ePro\Model\Magento\Product::FORCING_QTY_TYPE_BACKORDERS) {
            // M2ePro\TRANSLATIONS
            // During the Quantity Calculation the Settings in the "Backorders" field were taken into consideration.
            $this->addWarningMessage('During the Quantity Calculation the Settings in the "Backorders" ' .
                'field were taken into consideration.');
        }
    }

    //########################################
}
