<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Translation\Connector\Product\Add;

class Dispatcher extends \Ess\M2ePro\Model\AbstractModel
{
    private $logsActionId = NULL;

    protected $activeRecordFactory;
    protected $ebayFactory;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        $this->ebayFactory = $ebayFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    // ########################################

    /**
     * @param array|\Ess\M2ePro\Model\Listing\Product $products
     * @param array $params
     * @return int
     */
    public function process($products, array $params = array())
    {
        $this->logsActionId = $this->activeRecordFactory->getObject('Listing\Log')->getResource()->getNextActionId();
        $params['logs_action_id'] = $this->logsActionId;

        $tempProducts = $this->prepareProducts($products);
        $sortedProducts = $this->sortProducts($tempProducts);

        $results = array();

        foreach ($sortedProducts as $chunk) {

            $products = (array)$chunk['products'];

            if (count($products) <= 0) {
                continue;
            }

            $params['source_language'] = $chunk['language']['source'];
            $params['target_language'] = $chunk['language']['target'];
            $params['service']         = $chunk['service'];

            for ($i=0; $i<count($products);$i+=100) {
                $productsForRequest = array_slice($products,$i,100);
                $results[] = $this->processProducts($productsForRequest, $params);
            }
        }

        return $this->getHelper('Data')->getMainStatus($results);
    }

    // ########################################

    public function getLogsActionId()
    {
        return (int)$this->logsActionId;
    }

    // ########################################

    /**
     * @param array $products
     * @param array $params
     * @return int
     */
    protected function processProducts(array $products, array $params = array())
    {
        try {

            $dispatcher = $this->modelFactory->getObject('Translation\Connector\Dispatcher');

            $connector = $dispatcher->getConnector('product', 'add', 'multipleRequester', $params);
            $connector->setListingsProducts($products);

            $connector->process();

            return $connector->getStatus();

        } catch (\Exception $exception) {

            $this->getHelper('Module\Exception')->process($exception);

            $logModel = $this->activeRecordFactory->getObject('Listing\Log');
            $logModel->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);

            $initiator = $this->recognizeInitiatorForLogging($params);

            foreach ($products as $product) {

                /** @var \Ess\M2ePro\Model\Listing\Product $product */

                $logModel->addProductMessage(
                    $product->getListingId(),
                    $product->getProductId(),
                    $product->getId(),
                    $initiator,
                    $this->logsActionId,
                    \Ess\M2ePro\Model\Listing\Log::ACTION_TRANSLATE_PRODUCT,
                    $exception->getMessage(),
                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                    \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
                );
            }

            return \Ess\M2ePro\Helper\Data::STATUS_ERROR;
        }
    }

    // ########################################

    protected function prepareProducts($products)
    {
        $productsTemp = array();

        if (!is_array($products)) {
            $products = array($products);
        }

        $productsIdsTemp = array();
        foreach ($products as $product) {

            $tempProduct = NULL;
            if ($product instanceof \Ess\M2ePro\Model\Listing\Product) {
                $tempProduct = $product;
            } else {
                $tempProduct = $this->ebayFactory->getObjectLoaded('Listing\Product',(int)$product);
            }

            if (in_array((int)$tempProduct->getId(),$productsIdsTemp)) {
                continue;
            }

            $productsIdsTemp[] = (int)$tempProduct->getId();
            $productsTemp[] = $tempProduct;
        }

        return $productsTemp;
    }

    protected function sortProducts($products)
    {
        $sortedProducts = array();

        foreach ($products as $product) {

            $listingId = $product->getListing()->getId();
            $translationData = $product->getSetting('additional_data',array('translation_service'),array());

            $key = $listingId
                .'_'.$translationData['from']['language']
                .'_'.$translationData['to']['language']
                .'_'.$product->getTranslationService();

            if (!isset($sortedProducts[$key])) {
                $sortedProducts[$key] = array(
                    'listing_id' => $listingId,
                    'language' => array(
                        'source' => $translationData['from']['language'],
                        'target' => $translationData['to']['language']
                    ),
                    'service' => $product->getTranslationService(),
                    'products'   => array()
                );
            }

            $sortedProducts[$key]['products'][] = $product;
        }

        return array_values($sortedProducts);
    }

    // ----------------------------------------

    protected function recognizeInitiatorForLogging(array $params)
    {
        $statusChanger = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_UNKNOWN;
        isset($params['status_changer']) && $statusChanger = $params['status_changer'];

        if ($statusChanger == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_UNKNOWN) {
            $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN;
        } else if ($statusChanger == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER) {
            $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_USER;
        } else {
            $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION;
        }

        return $initiator;
    }

    // ########################################

}