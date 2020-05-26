<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\RunReviseProducts
 */
class RunReviseProducts extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\ActionAbstract
{
    public function execute()
    {
        return $this->scheduleAction(
            \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
        );
    }

    protected function createUpdateScheduledActionsDataCallback($listingProduct, $action, array $params)
    {
        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $configurator */
        $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
        $configurator->enableAll();
        $tag = '/qty/lag_time/price/promotions/details/';
        $params['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER;

        return [
            'listing_product_id' => $listingProduct->getId(),
            'component'          => \Ess\M2ePro\Helper\Component\Walmart::NICK,
            'action_type'        => $action,
            'is_force'           => true,
            'tag'                => $tag,
            'additional_data'    => $this->getHelper('Data')->jsonEncode(
                [
                    'params'       => $params,
                    'configurator' => $configurator->getSerializedData()
                ]
            ),
        ];
    }
}
