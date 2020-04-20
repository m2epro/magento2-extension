<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub\Attributes
 */
class Attributes extends AbstractModel
{
    //########################################

    protected function check()
    {
        if (!$this->getProcessor()->getTypeModel()->isActualRealProductAttributes()) {
            $this->getProcessor()->getTypeModel()->resetProductAttributes(false);
        }

        if (!$this->getProcessor()->getTypeModel()->isActualVirtualProductAttributes()) {
            $this->getProcessor()->getTypeModel()->resetProductAttributes(false);
        }

        if (!$this->getProcessor()->getTypeModel()->isActualVirtualChannelAttributes()) {
            $this->getProcessor()->getTypeModel()->resetProductAttributes(false);
        }

        if (count($this->getProcessor()->getTypeModel()->getRealChannelAttributes()) ==
            count($this->getProcessor()->getTypeModel()->getRealProductAttributes())
        ) {
            $this->getProcessor()->getTypeModel()->setVirtualProductAttributes([], false);
            $this->getProcessor()->getTypeModel()->setVirtualChannelAttributes([], false);
        }
    }

    protected function execute()
    {
        return null;
    }

    //########################################
}
