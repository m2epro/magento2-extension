<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\RunReviseProducts
 */
class RunReviseProducts extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\ActionAbstract
{
    public function execute()
    {
        if ($this->getHelper('Data')->jsonDecode($this->getRequest()->getParam('is_realtime'))) {
            return $this->processConnector(
                \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
            );
        }

        return $this->scheduleAction(
            \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
        );
    }

    protected function createUpdateScheduledActionsDataCallback($listingProduct, $action, array $params)
    {
        $configurator = $this->modelFactory->getObject('Ebay_Listing_Product_Action_Configurator');
        $configurator->enableAll();
        $tag = '/qty/price/title/subtitle/description/images/categories/payment/shipping/return/other/';
        $params['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER;

        return [
            'listing_product_id' => $listingProduct->getId(),
            'component'          => \Ess\M2ePro\Helper\Component\Ebay::NICK,
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
