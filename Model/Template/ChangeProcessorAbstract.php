<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Template;

/**
 * Class \Ess\M2ePro\Model\Template\ChangeProcessorAbstract
 */
abstract class ChangeProcessorAbstract extends \Ess\M2ePro\Model\AbstractModel
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

    public function process(\Ess\M2ePro\Model\ActiveRecord\Diff $diff, array $affectedListingsProductsData)
    {
        if (empty($affectedListingsProductsData)) {
            return;
        }

        if (!$diff->isDifferent()) {
            return;
        }

        $listingsProductsInstructionsData = [];

        $statusInstructionCache = [];

        foreach ($affectedListingsProductsData as $affectedListingProductData) {
            $status = $affectedListingProductData['status'];

            if (isset($statusInstructionCache[$status])) {
                $instructionsData = $statusInstructionCache[$status];
            } else {
                $instructionsData = $this->getInstructionsData($diff, $status);
            }

            foreach ($instructionsData as $instructionData) {
                $listingsProductsInstructionsData[] = [
                    'listing_product_id' => $affectedListingProductData['id'],
                    'type'               => $instructionData['type'],
                    'initiator'          => $this->getInstructionInitiator(),
                    'priority'           => $instructionData['priority'],
                ];
            }
        }

        $this->activeRecordFactory->getObject('Listing_Product_Instruction')->getResource()
            ->add($listingsProductsInstructionsData);
    }

    //########################################

    abstract protected function getInstructionInitiator();

    // ---------------------------------------

    abstract protected function getInstructionsData(\Ess\M2ePro\Model\ActiveRecord\Diff $diff, $status);

    //########################################
}
