<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Product\Variation;

/**
 * Class \Ess\M2ePro\Model\Listing\Product\Variation\Updater
 */
abstract class Updater extends \Ess\M2ePro\Model\AbstractModel
{
    //########################################

    abstract public function process(\Ess\M2ePro\Model\Listing\Product $listingProduct);

    //########################################

    public function beforeMassProcessEvent()
    {
        return null;
    }

    public function afterMassProcessEvent()
    {
        return null;
    }

    //########################################
}
