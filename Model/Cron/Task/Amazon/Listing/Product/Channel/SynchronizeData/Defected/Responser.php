<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\Defected;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Listing\Product\Channel\SynchronizeData\Defected\Responser
 */
class Responser extends \Ess\M2ePro\Model\Amazon\Connector\Inventory\Get\Defected\ItemsResponser
{
    protected $synchronizationLog = null;

    protected $resourceConnection;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = []
    ) {
        $this->resourceConnection = $resourceConnection;
        parent::__construct(
            $response,
            $helperFactory,
            $modelFactory,
            $amazonFactory,
            $walmartFactory,
            $ebayFactory,
            $activeRecordFactory,
            $params
        );
    }

    //########################################

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
                $logType
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

    //########################################

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        $this->getSynchronizationLog()->addMessage(
            $this->getHelper('Module\Translation')->__($messageText),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
        );
    }

    //########################################

    protected function processResponseData()
    {
        try {
            $this->clearAllDefectedMessages();
            $this->updateReceivedDefectedListingsProducts();
        } catch (\Exception $e) {
            $this->getHelper('Module\Exception')->process($e);
            $this->getSynchronizationLog()->addMessageFromException($e);
        }
    }

    //########################################

    protected function clearAllDefectedMessages()
    {
        if (!isset($this->params['is_first_part']) || !$this->params['is_first_part']) {
            return;
        }

        $connection = $this->resourceConnection->getConnection();

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingsProductsIds = $listingProductCollection->getAllIds();

        foreach (array_chunk($listingsProductsIds, 1000) as $partIds) {
            $where = '`listing_product_id` IN ('.implode(',', $partIds).')';
            $connection->update(
                $this->activeRecordFactory->getObject('Amazon_Listing_Product')->getResource()->getMainTable(),
                [
                    'defected_messages' => null,
                ],
                $where
            );
        }
    }

    protected function updateReceivedDefectedListingsProducts()
    {
        $responseData = $this->getPreparedResponseData();
        $receivedItems = $responseData['data'];

        $keys = array_map(function ($el) {
            return (string)$el;
        }, array_keys($receivedItems));

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $listingProductCollection */
        $listingProductCollection = $this->amazonFactory->getObject('Listing\Product')->getCollection();
        $listingProductCollection->addFieldToFilter('sku', ['in' => $keys]);

        /** @var \Ess\M2ePro\Model\Listing\Product[] $defectedListingsProducts */
        $defectedListingsProducts = $listingProductCollection->getItems();

        foreach ($defectedListingsProducts as $listingProduct) {

            /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
            $amazonListingProduct = $listingProduct->getChildObject();

            $receivedData = $receivedItems[$amazonListingProduct->getSku()];

            $defectedMessage = [
                'attribute' => $receivedData['defected_attribute'],
                'value'     => $receivedData['current_value'],
                'type'      => $receivedData['defect_type'],
                'message'   => $receivedData['message'],
            ];

            $listingProduct->setSettings('defected_messages', [$defectedMessage])->save();
        }
    }

    //########################################

    protected function getSynchronizationLog()
    {
        if ($this->synchronizationLog !== null) {
            return $this->synchronizationLog;
        }

        $this->synchronizationLog = $this->activeRecordFactory->getObject('Synchronization\Log');
        $this->synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);
        $this->synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_LISTINGS);

        return $this->synchronizationLog;
    }

    //########################################
}
