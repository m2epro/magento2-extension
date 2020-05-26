<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Instruction\AutoActions;

use Ess\M2ePro\Model\AbstractModel;
use Ess\M2ePro\Model\Listing\Product\Instruction\Handler\HandlerInterface;
use Ess\M2ePro\Model\Listing\Auto\Actions\Listing as ActionsListing;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Instruction\AutoActions\Handler
 */
class Handler extends AbstractModel implements HandlerInterface
{
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->activeRecordFactory = $activeRecordFactory;
    }

    //########################################

    protected function getAffectedInstructionTypes()
    {
        return [
            ActionsListing::INSTRUCTION_TYPE_STOP,
            ActionsListing::INSTRUCTION_TYPE_STOP_AND_REMOVE,
        ];
    }

    //########################################

    public function process(\Ess\M2ePro\Model\Listing\Product\Instruction\Handler\Input $input)
    {
        if (!$input->hasInstructionWithTypes($this->getAffectedInstructionTypes())) {
            return;
        }

        $scheduledActionCollection = $this->activeRecordFactory
            ->getObject('Listing_Product_ScheduledAction')->getCollection();
        $scheduledActionCollection->addFieldToFilter('listing_product_id', $input->getListingProduct()->getId());

        /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction $scheduledAction */
        $scheduledAction = $scheduledActionCollection->getFirstItem();

        $params = [];

        if ($input->hasInstructionWithType(ActionsListing::INSTRUCTION_TYPE_STOP_AND_REMOVE)) {
            if (!$input->getListingProduct()->isStoppable()) {
                $removeHandler = $this->modelFactory->getObject('Walmart_Listing_Product_RemoveHandler');
                $removeHandler->setListingProduct($input->getListingProduct());
                $removeHandler->process();

                return;
            }

            $params['remove'] = true;
        }

        $scheduledActionData = [
            'listing_product_id' => $input->getListingProduct()->getId(),
            'component'          => \Ess\M2ePro\Helper\Component\Walmart::NICK,
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
