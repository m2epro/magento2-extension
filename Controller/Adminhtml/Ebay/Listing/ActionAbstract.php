<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

abstract class ActionAbstract extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;
    /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Log */
    private $listingLogResource;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        \Ess\M2ePro\Model\ResourceModel\Listing\Log $listingLogResource,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->translationHelper = $translationHelper;
        $this->listingLogResource = $listingLogResource;
    }

    protected function isRealtimeProcess(): bool
    {
        return (bool)\Ess\M2ePro\Helper\Json::decode($this->getRequest()->getParam('is_realtime'));
    }

    protected function processRealtime(
        \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Realtime\AbstractRealtime $processor,
        array $params = []
    ) {
        if (!$processor->isAllowed()) {
            $message = 'The action is temporarily unavailable. M2E Pro Server is under maintenance.';
            $message .= ' Please try again later.';

            return $this->setRawContent($this->translationHelper->__($message));
        }

        if (!$listingsProductsIds = $this->getRequest()->getParam('selected_products')) {
            return $this->setRawContent('You should select Products');
        }

        $logsActionId = $this->createLogsActionId();
        $listingsProducts = $this->loadProducts($listingsProductsIds);

        if (empty($listingsProducts)) {
            $this->setJsonContent(['result' => 'error', 'action_id' => $logsActionId]);

            return $this->getResult();
        }

        $result = $processor->process($listingsProducts, $params, $logsActionId);

        if ($result->isError()) {
            $this->setJsonContent(['result' => 'error', 'action_id' => $logsActionId]);

            return $this->getResult();
        }

        if ($result->isWarning()) {
            $this->setJsonContent(['result' => 'warning', 'action_id' => $logsActionId]);

            return $this->getResult();
        }

        $this->setJsonContent(['result' => 'success', 'action_id' => $logsActionId]);

        return $this->getResult();
    }

    protected function createScheduleAction(
        \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Schedule\AbstractSchedule $processor,
        array $params = []
    ) {
        if (!$listingsProductsIds = $this->getRequest()->getParam('selected_products')) {
            return $this->setRawContent('You should select Products');
        }

        $logsActionId = $this->createLogsActionId();
        $listingsProducts = $this->loadProducts($listingsProductsIds);

        if (empty($listingsProducts)) {
            $this->setJsonContent(['result' => 'error', 'action_id' => $logsActionId]);

            return $this->getResult();
        }

        $processor->process($listingsProducts, $params, $logsActionId);

        $this->setJsonContent(['result' => 'success', 'action_id' => $logsActionId]);

        return $this->getResult();
    }

    private function createLogsActionId(): int
    {
        return $this->listingLogResource->getNextActionId();
    }

    /**
     * @param string $listingsProductsIds
     *
     * @return \Ess\M2ePro\Model\Listing\Product[]
     */
    private function loadProducts(string $listingsProductsIds): array
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $productsCollection */
        $productsCollection = $this->ebayFactory->getObject('Listing_Product')->getCollection();
        $productsCollection->addFieldToFilter('id', explode(',', $listingsProductsIds));

        /** @var \Ess\M2ePro\Model\Listing\Product[] $listingsProducts */
        $listingsProducts = $productsCollection->getItems();

        return $listingsProducts;
    }
}
