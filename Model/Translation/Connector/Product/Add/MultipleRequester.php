<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Translation\Connector\Product\Add;

class MultipleRequester extends \Ess\M2ePro\Model\Translation\Connector\Command\Pending\Requester
{
    // ########################################

    /**
     * @var \Ess\M2ePro\Model\Marketplace|null
     */
    protected $marketplace = NULL;

    protected $logsActionId = NULL;
    protected $neededRemoveLocks = array();

    protected $status = \Ess\M2ePro\Helper\Data::STATUS_SUCCESS;

    protected $listingsProducts = array();
    protected $listingProductRequestsData = array();

    const MAX_LIFE_TIME_INTERVAL = 864000; // 10 days

    protected $activeRecordFactory;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\Account $account,
        array $params
    )
    {
        $defaultParams = array(
            'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_UNKNOWN
        );
        $params = array_merge($defaultParams, $params);

        if (isset($params['logs_action_id'])) {
            $this->logsActionId = (int)$params['logs_action_id'];
            unset($params['logs_action_id']);
        } else {
            $this->logsActionId = $activeRecordFactory->getObject('Listing\Log')->getResource()->getNextActionId();
        }

        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $account, $params);
    }

    // ########################################

    public function setListingsProducts(array $listingsProducts)
    {
        if (count($listingsProducts) == 0) {
            throw new \Ess\M2ePro\Model\Exception('Product Connector has received empty array');
        }

        foreach($listingsProducts as $listingProduct) {
            if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
                throw new \Ess\M2ePro\Model\Exception('Product Connector has received invalid Product data type');
            }
        }

        $translationData    = $listingsProducts[0]->getSetting('additional_data',array('translation_service'),array());
        $tempSourceLanguage = $translationData['from']['language'];
        $tempTargetLanguage = $translationData['to']['language'];
        $tempService      = $listingsProducts[0]->getTranslationService();

        $tempListing = $listingsProducts[0]->getListing();
        foreach($listingsProducts as $listingProduct) {
            if ($tempListing->getId() != $listingProduct->getListing()->getId()) {
                throw new \Ess\M2ePro\Model\Exception(
                    'Product Connector has received Products from different Listings'
                );
            }

            $translationData = $listingProduct->getSetting('additional_data',array('translation_service'),array());

            if ($tempSourceLanguage != $translationData['from']['language']) {
                throw new \Ess\M2ePro\Model\Exception('Product Connector has received Products from different
                    source languages');
            }

            if ($tempTargetLanguage != $translationData['to']['language']) {
                throw new \Ess\M2ePro\Model\Exception('Product Connector has received Products from different
                    target languages');
            }

            if ($tempService != $listingProduct->getTranslationService()) {
                throw new \Ess\M2ePro\Model\Exception('Product Connector has received Products from different
                    Translation Services');
            }
        }

        $this->account     = $listingsProducts[0]->getListing()->getAccount();
        $this->marketplace = $listingsProducts[0]->getListing()->getMarketplace();

        $listingsProducts = $this->filterLockedListingsProducts($listingsProducts);
        $listingsProducts = $this->prepareListingsProducts($listingsProducts);

        $this->listingsProducts = array_values($listingsProducts);
    }

    // ########################################

    public function __destruct()
    {
        $this->checkUnlockListings();
    }

    // ########################################

    public function getCommand()
    {
        return array('product','add','entities');
    }

    // ########################################

    public function getStatus()
    {
        return $this->status;
    }

    protected function setStatus($status)
    {
        if (!in_array($status,array(
            \Ess\M2ePro\Helper\Data::STATUS_ERROR,
            \Ess\M2ePro\Helper\Data::STATUS_WARNING,
            \Ess\M2ePro\Helper\Data::STATUS_SUCCESS))) {
            return;
        }

        if ($status == \Ess\M2ePro\Helper\Data::STATUS_ERROR) {
            $this->status = \Ess\M2ePro\Helper\Data::STATUS_ERROR;
            return;
        }

        if ($this->status == \Ess\M2ePro\Helper\Data::STATUS_ERROR) {
            return;
        }

        if ($status == \Ess\M2ePro\Helper\Data::STATUS_WARNING) {
            $this->status = \Ess\M2ePro\Helper\Data::STATUS_WARNING;
            return;
        }

        if ($this->status == \Ess\M2ePro\Helper\Data::STATUS_WARNING) {
            return;
        }

        $this->status = \Ess\M2ePro\Helper\Data::STATUS_SUCCESS;
    }

    // ########################################

    protected function getRequestData()
    {
         $requestData = array(
            'service'      => $this->params['service'],
            'source_language' => $this->params['source_language'],
            'target_language' => $this->params['target_language'],
            'products' => array()
        );

        foreach ($this->listingsProducts as $listingProduct) {

            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */

            $tempData = $listingProduct->getSetting('additional_data', array('translation_service', 'from'), array());

            $listingProductRequestData = array(
                'title'          => $tempData['description']['title'],
                'subtitle'       => $tempData['description']['subtitle'],
                'description'    => $tempData['description']['description'],
                'sku'            => $tempData['sku'],
                'item_specifics' => $tempData['item_specifics'],
                'category'       => $tempData['category']
            );

            $this->listingProductRequestsData[$listingProduct->getId()] = $listingProductRequestData;
            $requestData['products'][] = $listingProductRequestData;
        }

        return $requestData;
    }

    // ########################################

    public function process()
    {
        $this->setStatus(\Ess\M2ePro\Helper\Data::STATUS_SUCCESS);

        if (count($this->listingsProducts) <= 0) {
            return;
        }

        $this->updateOrLockListingProducts();
        parent::process();

        // When all items are failed in response

        $responseData = $this->getResponse()->getResponseData();

        (isset($responseData['data']['messages'])) && $tempMessages = $responseData['data']['messages'];
        if (isset($tempMessages) && is_array($tempMessages) && count($tempMessages) > 0) {
            $this->setStatus(\Ess\M2ePro\Helper\Data::STATUS_ERROR);
        }

        $this->checkUnlockListings();
    }

    // ########################################

    protected function getProcessingParams()
    {
        $listingProductIds = array();
        foreach ($this->listingsProducts as $listingProduct) {
            $listingProductIds[] = $listingProduct->getId();
        }

        return array_merge(
            parent::getProcessingParams(),
            array(
                'listing_product_ids' => array_unique($listingProductIds),
            )
        );
    }

    protected function getResponserParams()
    {
        $tempProductsData = array();

        foreach ($this->listingsProducts as $listingProduct) {
            $tempProductsData[$listingProduct->getId()] =
                isset($this->listingProductRequestsData[$listingProduct->getId()])
                    ? $this->listingProductRequestsData[$listingProduct->getId()]
                    : array();
        }

        return array(
            'account_id'     => $this->account->getId(),
            'marketplace_id' => $this->marketplace->getId(),
            'logs_action_id' => $this->logsActionId,
            'status_changer' => $this->params['status_changer'],
            'params'         => $this->params,
            'products'       => $tempProductsData
        );
    }

    // ########################################

    protected function updateOrLockListingProducts()
    {
        foreach ($this->listingsProducts as $product) {

            /** @var $product \Ess\M2ePro\Model\Listing\Product */

            $lockItem = $this->modelFactory->getObject('Lock\Item\Manager');
            $lockItem->setNick(\Ess\M2ePro\Helper\Component\Ebay::NICK.'_listing_product_'.$product->getId());

            if (!$lockItem->isExist()) {
                $lockItem->create();
                $lockItem->makeShutdownFunction();
                $this->neededRemoveLocks[$product->getId()] = $lockItem;
            }

            $lockItem->activate();
        }
    }

    protected function checkUnlockListings()
    {
        foreach ($this->neededRemoveLocks as $lockItem) {
            $lockItem->isExist() && $lockItem->remove();
        }
        $this->neededRemoveLocks = array();
    }

    // ########################################

    protected function addListingsProductsLogsMessage(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                                      $text, $type = \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
                                                      $priority = \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM)
    {
        $action =\Ess\M2ePro\Model\Listing\Log::ACTION_TRANSLATE_PRODUCT;

        if ($this->params['status_changer'] == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_UNKNOWN) {
            $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN;
        } else if ($this->params['status_changer'] == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER) {
            $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_USER;
        } else {
            $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION;
        }

        switch ($type) {
            case \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR:
                $this->setStatus(\Ess\M2ePro\Helper\Data::STATUS_ERROR);
                break;
            case \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING:
                $this->setStatus(\Ess\M2ePro\Helper\Data::STATUS_WARNING);
                break;
            case \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS:
            case \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE:
                $this->setStatus(\Ess\M2ePro\Helper\Data::STATUS_SUCCESS);
                break;
            default:
                $this->setStatus(\Ess\M2ePro\Helper\Data::STATUS_ERROR);
                break;
        }

        $logModel = $this->activeRecordFactory->getObject('Listing\Log');
        $logModel->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);

        $logModel->addProductMessage($listingProduct->getListingId() ,
                                     $listingProduct->getProductId() ,
                                     $listingProduct->getId() ,
                                     $initiator ,
                                     $this->logsActionId ,
                                     $action , $text, $type , $priority);
    }

    // ########################################

    protected function filterLockedListingsProducts($listingsProducts)
    {
        foreach ($listingsProducts as $key => $listingProduct) {

            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */

            if ($listingProduct->isSetProcessingLock(NULL) ||
                $listingProduct->isSetProcessingLock('in_action') ||
                $listingProduct->isSetProcessingLock('translation_action')) {

                // M2ePro\TRANSLATIONS
                // Another Action is being processed. Try again when the Action is completed.
                $this->addListingsProductsLogsMessage(
                    $listingProduct, 'Another Action is being processed. Try again when the Action is completed.',
                    \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                    \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM
                );

                unset($listingsProducts[$key]);
                continue;
            }
        }

        return $listingsProducts;
    }

    protected function prepareListingsProducts($listingProducts)
    {
        foreach ($listingProducts as $key => $listingProduct) {

            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */

            if (!$listingProduct->getChildObject()->isTranslatable()) {

                // M2ePro\TRANSLATIONS
                // 'Product is Translated or being Translated'
                $this->addListingsProductsLogsMessage($listingProduct, 'Product is Translated or being Translated',
                                                      \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                                                      \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM);
                unset($listingProducts[$key]);
                continue;
            }

            $listingProduct->getChildObject()->setData(
                'translation_status',
               \Ess\M2ePro\Model\Ebay\Listing\Product::TRANSLATION_STATUS_IN_PROGRESS
            )->save();
        }

        return array_values($listingProducts);
    }

    // ########################################
}