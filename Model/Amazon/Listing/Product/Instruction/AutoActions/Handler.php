<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\AutoActions;

use \Ess\M2ePro\Model\Listing\Product\Instruction\Handler\HandlerInterface;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Instruction\AutoActions\Handler
 */
class Handler extends \Ess\M2ePro\Model\AbstractModel implements HandlerInterface
{
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    protected function getAffectedInstructionTypes()
    {
        return [
            \Ess\M2ePro\Model\Listing\Auto\Actions\Listing::INSTRUCTION_TYPE_STOP,
            \Ess\M2ePro\Model\Listing\Auto\Actions\Listing::INSTRUCTION_TYPE_STOP_AND_REMOVE,
        ];
    }

    //########################################

    public function process(\Ess\M2ePro\Model\Listing\Product\Instruction\Handler\Input $input)
    {
        if (!$input->hasInstructionWithTypes($this->getAffectedInstructionTypes())) {
            return;
        }

        $listingProduct = $input->getListingProduct();

        $scheduledActionCollection = $this->activeRecordFactory
            ->getObject('Listing_Product_ScheduledAction')->getCollection();
        $scheduledActionCollection->addFieldToFilter('listing_product_id', $listingProduct->getId());

        /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction $scheduledAction */
        $scheduledAction = $scheduledActionCollection->getFirstItem();

        $params = [];

        if ($input->hasInstructionWithType(
            \Ess\M2ePro\Model\Listing\Auto\Actions\Listing::INSTRUCTION_TYPE_STOP_AND_REMOVE
        )) {
            if (!$input->getListingProduct()->isStoppable()) {
                /** @var \Ess\M2ePro\Model\Amazon\Listing\Product\RemoveHandler $removeHandler */
                $removeHandler = $this->modelFactory->getObject('Amazon_Listing_Product_RemoveHandler');
                $removeHandler->setListingProduct($input->getListingProduct());
                $removeHandler->process();

                return;
            }

            $params['remove'] = true;
        }

        $scheduledActionData = [
            'listing_product_id' => $listingProduct->getId(),
            'component'          => \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'action_type'        => \Ess\M2ePro\Model\Listing\Product::ACTION_STOP,
            'is_force'           => true,
            'additional_data'    => $this->getHelper('Data')->jsonEncode(['params' => $params]),
        ];

        $scheduledAction->addData($scheduledActionData);

        /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction\Manager $scheduledActionManager */
        $scheduledActionManager = $this->modelFactory->getObject('Listing_Product_ScheduledAction_Manager');

        if ($scheduledAction->getId()) {
            $scheduledActionManager->updateAction($scheduledAction);
        } else {
            $scheduledActionManager->addAction($scheduledAction);
        }
    }

    //########################################
}
