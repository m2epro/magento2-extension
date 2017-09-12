<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Synchronization\Templates\Synchronization;

abstract class Inspector extends \Ess\M2ePro\Model\AbstractModel
{
    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    abstract public function isMeetListRequirements(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        $needSynchRulesCheckIfLocked = true
    );

    abstract public function isMeetRelistRequirements(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        $needSynchRulesCheckIfLocked = true
    );

    abstract public function isMeetStopRequirements(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        $needSynchRulesCheckIfLocked = true
    );

    // ---------------------------------------

    abstract public function isMeetReviseQtyRequirements(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        $needSynchRulesCheckIfLocked = true
    );

    abstract public function isMeetRevisePriceRequirements(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        $needSynchRulesCheckIfLocked = true
    );

    //########################################

    protected function isTriedToList(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $listingProductAdditionalData = $listingProduct->getAdditionalData();
        if (empty($listingProductAdditionalData['last_list_attempt_date'])) {
            return false;
        }

        $lastListAttemptDate = new \DateTime(
            $listingProductAdditionalData['last_list_attempt_date'], new \DateTimeZone('UTC')
        );

        $minDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $minDate->modify('- 3 days');

        if ((int)$lastListAttemptDate->format('U') < (int)$minDate->format('U')) {
            return false;
        }

        return true;
    }

    protected function isChangeInitiatorOnlyInspector(\Ess\M2ePro\Model\Listing\Product $listingProduct)
    {
        $changeInitiators = $listingProduct->getData('change_initiators');

        if (empty($changeInitiators)) {
            return false;
        }

        $changeInitiators = (array)explode(',', $changeInitiators);
        $changeInitiators = array_unique($changeInitiators);

        if (count($changeInitiators) != 1) {
            return false;
        }

        if ((int)reset($changeInitiators) != \Ess\M2ePro\Model\ProductChange::INITIATOR_INSPECTOR) {
            return false;
        }

        return true;
    }

    //########################################
}