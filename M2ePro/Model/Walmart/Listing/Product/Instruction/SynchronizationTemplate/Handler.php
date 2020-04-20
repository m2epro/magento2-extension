<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Instruction\SynchronizationTemplate;

use Ess\M2ePro\Model\AbstractModel;
use Ess\M2ePro\Model\Listing\Product\Instruction\Handler\HandlerInterface;
use Ess\M2ePro\Model\Listing\Product\Instruction\SynchronizationTemplate\Checker\AbstractModel as CheckerAbstract;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Instruction\SynchronizationTemplate\Handler
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

    public function process(\Ess\M2ePro\Model\Listing\Product\Instruction\Handler\Input $input)
    {
        $scheduledActionCollection = $this->activeRecordFactory
            ->getObject('Listing_Product_ScheduledAction')->getCollection();
        $scheduledActionCollection->addFieldToFilter('listing_product_id', $input->getListingProduct()->getId());

        /** @var \Ess\M2ePro\Model\Listing\Product\ScheduledAction $scheduledAction */
        $scheduledAction = $scheduledActionCollection->getFirstItem();

        /** @var \Ess\M2ePro\Model\Listing\Product\Instruction\SynchronizationTemplate\Checker\Input $checkerInput */
        $checkerInput = $this->modelFactory
            ->getObject('Listing_Product_Instruction_SynchronizationTemplate_Checker_Input');
        $checkerInput->setListingProduct($input->getListingProduct());
        $checkerInput->setInstructions($input->getInstructions());

        if ($scheduledAction->getId()) {
            $checkerInput->setScheduledAction($scheduledAction);
        }

        $params = [
            'status_changer' => \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_SYNCH
        ];

        foreach ($this->getAllCheckers() as $checker) {
            $checkerModel = $this->getCheckerModel($checker);
            $checkerModel->setInput($checkerInput);

            if (!$checkerModel->isAllowed()) {
                continue;
            }

            $checkerModel->process($params);
        }
    }

    //########################################

    protected function getAllCheckers()
    {
        return [
            'NotListed',
            'Active',
            'Inactive',
        ];
    }

    /**
     * @param $checkerNick
     * @return \Ess\M2ePro\Model\Listing\Product\Instruction\SynchronizationTemplate\Checker\AbstractModel
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getCheckerModel($checkerNick)
    {
        $checkerModelName = 'Walmart\Listing\Product\Instruction\SynchronizationTemplate\Checker\\' . $checkerNick;

        if (!$this->modelFactory->canCreateObject($checkerModelName)) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                sprintf('Checker model "%s" does not exist.', $checkerModelName)
            );
        }

        /** @var CheckerAbstract $checkerModel */
        $checkerModel = $this->modelFactory->getObject($checkerModelName);

        if (!($checkerModel instanceof CheckerAbstract)) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                sprintf(
                    'Checker model "%s" does not extends
                   "\Ess\M2ePro\Model\Walmart\Listing\Product\Instruction\SynchronizationTemplate\Checker\AbstractModel"
                    class',
                    $checkerModelName
                )
            );
        }

        return $checkerModel;
    }

    //########################################
}
