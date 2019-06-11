<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Connector\Product\ListAction;

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
        return 'Walmart\Connector\Product\ListAction\ProcessingRunner';
    }

    // ########################################

    public function getCommand()
    {
        return array('product', 'add', 'entities');
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
                ->getObject('Walmart\Listing\Product\Action\Type\ListAction\SkuResolver');
            $skuResolver->setListingProduct($this->listingProduct);

            $sku = $skuResolver->resolve();

            if (count($skuResolver->getMessages()) > 0) {

                foreach ($skuResolver->getMessages() as $message) {
                    $this->getLogger()->logListingProductMessage(
                        $this->listingProduct,
                        $message,
                        \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
                    );
                }
            }

            if (is_null($sku)) {
                return false;
            }
        }

        $productsData = $this->receiveProductsData($sku);

        if (isset($productsData[$sku])) {
            $productData = $productsData[$sku];

            $linking = $this->modelFactory
                ->getObject('Walmart\Listing\Product\Action\Type\ListAction\Linking');
            $linking->setListingProduct($this->listingProduct);
            $linking->setSku($sku);
            $linking->setProductIdentifiers(array(
                'wpid'    => $productData['wpid'],
                'item_id' => $productData['item_id'],
                'gtin'    => $productData['gtin'],
                'upc'     => isset($productData['upc']) ? $productData['upc'] : null,
                'ean'     => isset($productData['ean']) ? $productData['ean'] : null,
                'isbn'    => isset($productData['isbn']) ? $productData['isbn'] : null,
            ));
            $linking->link();
        } else {
            if ($walmartListingProduct->getSku()) {
                $walmartListingProduct->addData(array(
                    'sku'     => null,
                    'wpid'    => null,
                    'item_id' => null,
                    'gtin'    => null,
                    'upc'     => null,
                    'ean'     => null,
                    'isbn'    => null,
                ));
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

        $requestData = array(
            'account'    => $this->listingProduct->getAccount()->getChildObject()->getServerHash(),
            'return_now' => true,
            'only_items' => $onlyItems,
        );

        /** @var \Ess\M2ePro\Model\Walmart\Connector\Dispatcher $dispatcher */
        $dispatcher = $this->modelFactory->getObject('Walmart\Connector\Dispatcher');

        $connector = $dispatcher->getVirtualConnector(
            'inventory', 'get', 'items',
            $requestData, null, null
        );

        try {
            $connector->process();
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);

            return array();
        }

        $responseData = $connector->getResponseData();

        if (empty($responseData['data'])) {
            return array();
        }

        $productsData = array();

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
        $resultListingProducts = array();

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