<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\Manual\Schedule;

use Ess\M2ePro\Model\ResourceModel\Listing as ListingResourceModel;

class ReviseAction extends AbstractSchedule
{
    /** @var \Ess\M2ePro\Model\Ebay\Listing\LogFactory */
    private $logFactory;
    /** @var \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Revise\Checker */
    private $reviseChecker;

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Listing\LogFactory $logFactory,
        \Ess\M2ePro\Model\Ebay\Listing\Product\Action\Type\Revise\Checker $reviseChecker,
        \Ess\M2ePro\Model\Listing\Product\ScheduledActionFactory $scheduledActionFactory,
        ListingResourceModel\Product\ScheduledAction\CollectionFactory $scheduledActionCollectionFactory,
        \Ess\M2ePro\Model\Listing\Product\ScheduledAction\Manager $scheduledActionManager,
        \Ess\M2ePro\Model\Listing\Product\LockManagerFactory $lockManagerFactory
    ) {
        parent::__construct(
            $scheduledActionFactory,
            $scheduledActionCollectionFactory,
            $scheduledActionManager,
            $lockManagerFactory
        );
        $this->logFactory = $logFactory;
        $this->reviseChecker = $reviseChecker;
    }

    protected function getAction(): int
    {
        return \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE;
    }

    protected function prepareOrFilterProducts(array $listingsProducts): array
    {
        return $listingsProducts;
    }

    protected function createScheduleAction(
        \Ess\M2ePro\Model\Listing\Product $product,
        array $params
    ): ?\Ess\M2ePro\Model\Listing\Product\ScheduledAction {
        $checkerResult = $this->reviseChecker->fullCalculate($product);
        if (empty($checkerResult->getTags())) {
            $this->writeLog($product);

            return null;
        }

        $params['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER;

        $data = [
            'listing_product_id' => $product->getId(),
            'component' => \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'action_type' => $this->getAction(),
            'is_force' => true,
            'tag' => '/' . implode('/', $checkerResult->getTags()) . '/',
            'additional_data' => \Ess\M2ePro\Helper\Json::encode(
                [
                    'params' => $params,
                    'configurator' => $checkerResult->getConfigurator()->getSerializedData(),
                ]
            ),
        ];

        return $this->createAction($data);
    }

    private function writeLog(\Ess\M2ePro\Model\Listing\Product $listingProduct): void
    {
        $ebayListingProduct = $listingProduct->getChildObject();

        /** @var \Ess\M2ePro\Model\Ebay\Listing\Log $log */
        $log = $this->logFactory->create();

        $log->addProductMessage(
            $listingProduct->getListingId(),
            $ebayListingProduct->getId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_USER,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_REVISE_PRODUCT_ON_COMPONENT,
            'Item(s) were not revised. M2E Pro did not detect any relevant product changes to be updated.',
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_INFO,
            []
        );
    }
}
