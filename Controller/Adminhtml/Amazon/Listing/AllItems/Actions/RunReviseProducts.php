<?php

declare(strict_types=1);

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\AllItems\Actions;

class RunReviseProducts extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\ActionAbstract
{
    private \Ess\M2ePro\Model\Amazon\Listing\Product\Action\ConfiguratorFactory $amazonConfiguratorFactory;

    public function __construct(
        \Ess\M2ePro\Model\Amazon\Listing\Product\Action\ConfiguratorFactory $amazonConfiguratorFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);
        $this->amazonConfiguratorFactory = $amazonConfiguratorFactory;
    }

    public function execute()
    {
        return $this->scheduleAction(\Ess\M2ePro\Model\Listing\Product::ACTION_REVISE);
    }

    protected function createUpdateScheduledActionsDataCallback(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        int $action,
        array $params
    ): array {
        $configurator = $this->amazonConfiguratorFactory->create();
        $configurator->enableAll();
        $tag = '/qty/price/details/';
        $params['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER;

        if (isset($params['switch_to'])) {
            $configurator->disableAll();
            $configurator->allowQty();
            $tag = '/qty/';
        } elseif ($listingProduct->getChildObject()->getVariationManager()->isRelationParentType()) {
            $configurator->disableAll();
            $configurator->allowDetails();
            $tag = '/details/';
        }

        return [
            'listing_product_id' => $listingProduct->getId(),
            'component' => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'action_type' => $action,
            'is_force' => true,
            'tag' => $tag,
            'additional_data' => \Ess\M2ePro\Helper\Json::encode(
                [
                    'params' => $params,
                    'configurator' => $configurator->getSerializedData(),
                ]
            ),
        ];
    }
}
