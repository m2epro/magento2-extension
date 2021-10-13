<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\RunReviseProducts
 */
class RunReviseProducts extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\ActionAbstract
{
    //########################################

    public function execute()
    {
        return $this->scheduleAction(
            \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE
        );
    }

    //########################################

    protected function createUpdateScheduledActionsDataCallback($listingProduct, $action, array $params)
    {
        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\Action\Configurator $configurator */
        $configurator = $this->modelFactory->getObject('Amazon_Listing_Product_Action_Configurator');
        $configurator->enableAll();
        $tag = '/qty/price/details/images/';
        $params['status_changer'] = \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER;

        if (isset($params['switch_to'])) {
            $configurator->disableAll();
            $configurator->allowQty();
            $tag = '/qty/';
        } elseif ($listingProduct->getChildObject()->getVariationManager()->isRelationParentType()) {
            $configurator->disableAll();
            $configurator->allowImages();
            $configurator->allowDetails();
            $tag = '/details/images/';
        }

        return [
            'listing_product_id' => $listingProduct->getId(),
            'component'          => \Ess\M2ePro\Helper\Component\Amazon::NICK,
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

    //########################################
}
