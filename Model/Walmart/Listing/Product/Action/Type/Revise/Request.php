<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Revise;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Revise\Request
 */
class Request extends \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Type\Request
{
    public const PRODUCT_ID_UPDATE_METADATA_KEY = 'product_id_update_details';

    //########################################

    protected function getActionData()
    {
        $data = array_merge(
            [
                'sku' => $this->getWalmartListingProduct()->getSku(),
                'wpid' => $this->getWalmartListingProduct()->getWpid(),
            ],
            $this->getQtyData(),
            $this->getLagTimeData(),
            $this->getPriceData(),
            $this->getRepricerData(),
            $this->getPromotionsData(),
            $this->getRequestDetailsData()
        );

        $params = $this->getParams();

        if (isset($params['changed_sku'])) {
            $data['sku'] = $params['changed_sku'];
            $data['is_need_sku_update'] = true;
        }

        if (isset($params['changed_identifier'])) {
            $changedType = strtoupper($params['changed_identifier']['type']);
            $changedValue = $params['changed_identifier']['value'];

            unset($data['product_id_data']);

            $data['product_id_data'] = [
                'type' => $changedType,
                'id' => $changedValue,
            ];

            $this->addMetaData(self::PRODUCT_ID_UPDATE_METADATA_KEY, $params['changed_identifier']);
            $data['is_need_product_id_update'] = true;
        }

        // walmart requirement is send price with some details data
        if ($this->getConfigurator()->isDetailsAllowed() && !$this->getConfigurator()->isPriceAllowed()) {
            $data['price'] = $this->getWalmartListingProduct()->getOnlinePrice();
        }

        return $data;
    }

    private function getRequestDetailsData(): array
    {
        if (!$this->getWalmartMarketplace()->isSupportedProductType()) {
            return $this->getDetailsData();
        }

        if (
            $this->getWalmartMarketplace()->isSupportedProductType()
            && $this->getWalmartListingProduct()->isExistsProductType()
        ) {
            return $this->getDetailsData();
        }

        return [];
    }
}
