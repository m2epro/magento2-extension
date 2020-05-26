<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder;

use \Ess\M2ePro\Model\Magento\Product as MagentoProduct;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\Qty
 */
class Qty extends AbstractModel
{
    //########################################

    public function getBuilderData()
    {
        $data = [
            'qty' => $this->getEbayListingProduct()->getQty()
        ];

        $this->checkQtyWarnings();

        return $data;
    }

    //########################################

    protected function checkQtyWarnings()
    {
        $qtyMode = $this->getEbayListingProduct()->getEbaySellingFormatTemplate()->getQtyMode();
        if ($qtyMode == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT_FIXED ||
            $qtyMode == \Ess\M2ePro\Model\Template\SellingFormat::QTY_MODE_PRODUCT) {

            $listingProductId = $this->getListingProduct()->getId();
            $productId = $this->getListingProduct()->getProductId();
            $storeId = $this->getListingProduct()->getListing()->getStoreId();

            if (!empty(MagentoProduct::$statistics[$listingProductId][$productId][$storeId]['qty'])) {
                $qtys = MagentoProduct::$statistics[$listingProductId][$productId][$storeId]['qty'];
                foreach ($qtys as $type => $override) {
                    $this->addQtyWarnings($type);
                }
            }
        }
    }

    /**
     * @param int $type
     */
    protected function addQtyWarnings($type)
    {
        if ($type === MagentoProduct::FORCING_QTY_TYPE_MANAGE_STOCK_NO) {
            $this->addWarningMessage('During the Quantity Calculation the Settings in the "Manage Stock No" '.
                'field were taken into consideration.');
        }

        if ($type === MagentoProduct::FORCING_QTY_TYPE_BACKORDERS) {
            $this->addWarningMessage('During the Quantity Calculation the Settings in the "Backorders" '.
                'field were taken into consideration.');
        }
    }

    //########################################
}
