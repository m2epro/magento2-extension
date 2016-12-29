<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Synchronization\ListingsProducts\Update\Defected;

class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\Defected\ItemsResponser
{
    protected $resourceConnection;

    protected $activeRecordFactory;

    protected $logsActionId = NULL;
    protected $synchronizationLog = NULL;

    // ########################################

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = array()
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($amazonFactory, $response, $helperFactory, $modelFactory, $params);
    }

    // ########################################

    protected function processResponseMessages()
    {
        parent::processResponseMessages();

        foreach ($this->getResponse()->getMessages()->getEntities() as $message) {

            if (!$message->isError() && !$message->isWarning()) {
                continue;
            }

            $logType = $message->isError() ? \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
                : \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;

            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module\Translation')->__($message->getText()),
                $logType,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );
        }
    }

    protected function isNeedProcessResponse()
    {
        if (!parent::isNeedProcessResponse()) {
            return false;
        }

        if ($this->getResponse()->getMessages()->hasErrorEntities()) {
            return false;
        }

        return true;
    }

    // ########################################

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        $this->getSynchronizationLog()->addMessage(
            $this->getHelper('Module\Translation')->__($messageText),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
            \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
        );
    }

    // ########################################

    protected function processResponseData()
    {
        try {

            $this->clearAllDefectedMessages();
            $this->updateReceivedDefectedListingsProducts();

        } catch (\Exception $exception) {

            $this->getHelper('Module\Exception')->process($exception);

            $this->getSynchronizationLog()->addMessage(
                $this->getHelper('Module\Translation')->__($exception->getMessage()),
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );
        }
    }

    // ########################################

    protected function clearAllDefectedMessages()
    {
        if (!isset($this->params['is_first_part']) || !$this->params['is_first_part']) {
            return false;
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingsProductsIds = $listingProductCollection->getAllIds();

        $amazonListingProductTable = $this->activeRecordFactory->getObject('Amazon\Listing\Product')
            ->getResource()
            ->getMainTable();

        foreach (array_chunk($listingsProductsIds,1000) as $partIds) {
            $this->resourceConnection->getConnection()->update(
                $amazonListingProductTable,
                array('defected_messages' => null),
                '`listing_product_id` IN ('.implode(',',$partIds).')'
            );
        }

        return true;
    }

    protected function updateReceivedDefectedListingsProducts()
    {
        $responseData = $this->getPreparedResponseData();
        $receivedItems = $responseData['data'];

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();

        $skus = array_map(function($el){ return (string)$el; }, array_keys($receivedItems));

        //ZF-5063: Segmentaion fault on preg_replace in Zend_Db_Statement
        if (count($skus) >= 250) {

            foreach ($skus as &$sku) {
                if (strpos($sku, '"') === false) {
                    continue;
                }

                $sku = str_replace('"', '', $sku);
            }
        }

        $listingProductCollection->addFieldToFilter('sku', array('in' => $skus));

        /** @var \Ess\M2ePro\Model\Listing\Product[] $defectedListingsProducts */
        $defectedListingsProducts = $listingProductCollection->getItems();

        foreach ($defectedListingsProducts as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            $receivedData = $receivedItems[$amazonListingProduct->getSku()];

            $defectedMessage = array(
                'attribute' => $receivedData['defected_attribute'],
                'value'     => $receivedData['current_value'],
                'type'      => $receivedData['defect_type'],
                'message'   => $receivedData['message'],
            );

            $listingProduct->setSettings('defected_messages', array($defectedMessage))->save();
        }
    }

    // ########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    protected function getAccount()
    {
        return $this->getObjectByParam('Account','account_id');
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    protected function getMarketplace()
    {
        return $this->getAccount()->getChildObject()->getMarketplace();
    }

    //-----------------------------------------

    protected function getSynchronizationLog()
    {
        if (!is_null($this->synchronizationLog)) {
            return $this->synchronizationLog;
        }

        $this->synchronizationLog = $this->activeRecordFactory->getObject('Synchronization\Log');
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $this->synchronizationLog->setSynchronizationTask(
            \Ess\M2ePro\Model\Synchronization\Log::TASK_LISTINGS_PRODUCTS
        );

        return $this->synchronizationLog;
    }

    // ########################################
}