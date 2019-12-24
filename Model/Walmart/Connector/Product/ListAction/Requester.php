<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Product\ListAction;

/**
 * Class \Ess\M2ePro\Model\Walmart\Connector\Product\ListAction\Requester
 */
class Requester extends \Ess\M2ePro\Model\Walmart\Connector\Product\Requester
{
    // ########################################

    public function setListingProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        parent::setListingProduct($listingProduct);

        $this->listingProduct->setData('synch_status', \Ess\M2ePro\Model\Listing\Product::SYNCH_STATUS_OK);
        $this->listingProduct->setData('synch_reasons', null);

        $additionalData = $listingProduct->getAdditionalData();
        unset($additionalData['synch_template_list_rules_note']);
        $this->listingProduct->setSettings('additional_data', $additionalData);

        $this->listingProduct->save();

        return $this;
    }

    // ########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Walmart_Connector_Product_ListAction_ProcessingRunner';
    }

    // ########################################

    public function getCommand()
    {
        return ['product', 'add', 'entities'];
    }

    // ########################################

    protected function getActionType()
    {
        return \Ess\M2ePro\Model\Listing\Product::ACTION_LIST;
    }

    protected function getLogsAction()
    {
        return \Ess\M2ePro\Model\Listing\Log::ACTION_LIST_PRODUCT_ON_COMPONENT;
    }

    // ########################################

    protected function validateListingProduct()
    {
        return $this->processAndValidateSku() && parent::validateListingProduct();
    }

    private function processAndValidateSku()
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $this->listingProduct->getChildObject();

        $sku = $walmartListingProduct->getSku();
        if (!$sku) {
            $skuResolver = $this->modelFactory
                ->getObject('Walmart_Listing_Product_Action_Type_ListAction_SkuResolver');
            $skuResolver->setListingProduct($this->listingProduct);

            $sku = $skuResolver->resolve();

            if (!empty($skuResolver->getMessages())) {
                foreach ($skuResolver->getMessages() as $message) {
                    $this->getLogger()->logListingProductMessage(
                        $this->listingProduct,
                        $message,
                        \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
                    );
                }
            }

            if ($sku === null) {
                return false;
            }
        }

        $productsData = $this->receiveProductsData($sku);

        if (isset($productsData[$sku])) {
            $productData = $productsData[$sku];

            $linking = $this->modelFactory
                ->getObject('Walmart_Listing_Product_Action_Type_ListAction_Linking');
            $linking->setListingProduct($this->listingProduct);
            $linking->setSku($sku);
            $linking->setProductIdentifiers([
                'wpid'    => $productData['wpid'],
                'item_id' => $productData['item_id'],
                'gtin'    => $productData['gtin'],
                'upc'     => isset($productData['upc']) ? $productData['upc'] : null,
                'ean'     => isset($productData['ean']) ? $productData['ean'] : null,
                'isbn'    => isset($productData['isbn']) ? $productData['isbn'] : null,
            ]);
            $linking->link();
        } else {
            if ($walmartListingProduct->getSku()) {
                $walmartListingProduct->addData([
                    'sku'     => null,
                    'wpid'    => null,
                    'item_id' => null,
                    'gtin'    => null,
                    'upc'     => null,
                    'ean'     => null,
                    'isbn'    => null,
                ]);
                $walmartListingProduct->save();
            }
        }

        $this->params['sku'] = $sku;

        return true;
    }

    private function receiveProductsData($sku)
    {
        $onlyItems = [
            [
                'type' => 'sku',
                'value' => $sku
            ]
        ];

        $requestData = [
            'account'    => $this->listingProduct->getAccount()->getChildObject()->getServerHash(),
            'return_now' => true,
            'only_items' => $onlyItems,
        ];

        /** @var \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $dispatcher */
        $dispatcher = $this->modelFactory->getObject('Walmart_Connector_Dispatcher');

        $connector = $dispatcher->getVirtualConnector(
            'inventory',
            'get',
            'items',
            $requestData,
            null,
            null
        );

        try {
            $connector->process();
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);

            return [];
        }

        $responseData = $connector->getResponseData();

        if (empty($responseData['data'])) {
            return [];
        }

        $productsData = [];

        foreach ($responseData['data'] as $productData) {
            $productsData[$productData['sku']] = $productData;
        }

        return $productsData;
    }

    // ########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Product[] $listingProducts
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    protected function filterChildListingProductsByStatus(array $listingProducts)
    {
        $resultListingProducts = [];

        foreach ($listingProducts as $listingProduct) {
            if (!$listingProduct->isNotListed() || !$listingProduct->isListable()) {
                continue;
            }

            $resultListingProducts[] = $listingProduct;
        }

        return $resultListingProducts;
    }

    // ########################################
}
