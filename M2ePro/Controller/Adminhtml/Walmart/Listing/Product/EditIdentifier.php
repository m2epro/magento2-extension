<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\EditIdentifier
 */
class EditIdentifier extends \Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\ActionAbstract
{
    //########################################

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::walmart_listings_m2epro');
    }

    //########################################

    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');
        $type = $this->getRequest()->getParam('type');
        $value = $this->getRequest()->getParam('value');

        $allowedTypes = ['gtin', 'upc', 'ean', 'isbn'];

        if (empty($productId) || empty($type) || empty($value) || !in_array($type, $allowedTypes)) {
            $this->setJsonContent([
                'result' => false,
                'message' => $this->__('Wrong parameters.')
            ]);

            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->walmartFactory->getObjectLoaded('Listing\Product', $productId);

        if (!$listingProduct->getId()) {
            $this->setJsonContent([
                'result' => false,
                'message' => $this->__('Listing product does not exist.')
            ]);

            return $this->getResult();
        }

        $lockManager = $this->modelFactory->getObject('Listing_Product_LockManager');
        $lockManager->setListingProduct($listingProduct);
        $lockManager->setInitiator(\Ess\M2ePro\Helper\Data::INITIATOR_USER);
        $lockManager->setLogsAction($this->getLogsAction(\Ess\M2ePro\Model\Listing\Product::ACTION_REVISE));

        if ($lockManager->checkLocking()) {
            $this->setJsonContent(
                [
                    'result'  => false,
                    'message' => $this->__(
                        'Another Action is being processed. Try again when the Action is completed.'
                    )
                ]
            );
            return $this->getResult();
        }

        $oldIdentifier = $listingProduct->getChildObject()->getData($type);
        if ($oldIdentifier === $value) {
            $this->setJsonContent([
                'result' => true,
                'message' => ''
            ]);

            return $this->getResult();
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product\Action\Configurator $configurator */
        $configurator = $this->modelFactory->getObject('Walmart_Listing_Product_Action_Configurator');
        $configurator->disableAll();
        $configurator->allowDetails();

        /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction $scheduledAction */
        $scheduledAction = $this->activeRecordFactory->getObject('Listing_Product_ScheduledAction');
        $scheduledAction->setData(
            [
                'listing_product_id' => $listingProduct->getId(),
                'component'          => \Ess\M2ePro\Helper\Component\Walmart::NICK,
                'action_type'        => \Ess\M2ePro\Model\Listing\Product::ACTION_REVISE,
                'is_force'           => true,
                'tag'                => '/details/',
                'additional_data'    => $this->getHelper('Data')->jsonEncode(
                    [
                        'params' => [
                            'changed_identifier' => [
                                'type'  => $type,
                                'value' => $value,
                            ],
                            'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER
                        ],
                        'configurator' => $configurator->getSerializedData(),
                    ]
                ),
            ]
        );

        /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction $existedScheduledAction */
        $existedScheduledAction = $this->activeRecordFactory->getObjectLoaded(
            'Listing_Product_ScheduledAction',
            $listingProduct->getId(),
            'listing_product_id',
            false
        );

        /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction\Manager $scheduledActionManager */
        $scheduledActionManager = $this->modelFactory->getObject('Listing_Product_ScheduledAction_Manager');

        if ($existedScheduledAction && $existedScheduledAction->getId()) {
            $scheduledActionManager->updateAction($scheduledAction);
        } else {
            $scheduledActionManager->addAction($scheduledAction);
        }

        $this->setJsonContent([
            'result' => true,
            'message' => ''
        ]);

        return $this->getResult();
    }

    //########################################
}
