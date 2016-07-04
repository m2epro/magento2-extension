<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Variation\Manager\Type\Relation\ParentRelation\Processor\Sub;

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

        if (!$this->getProcessor()->isGeneralIdSet()) {
            $this->getProcessor()->getTypeModel()->setChannelVariations(array(), false);
            $this->getProcessor()->getTypeModel()->setChannelAttributesSets(array(), false);
        }

        if (count($this->getProcessor()->getTypeModel()->getRealChannelAttributes()) ==
            count($this->getProcessor()->getTypeModel()->getRealProductAttributes())
        ) {
            $this->getProcessor()->getTypeModel()->setVirtualProductAttributes(array(), false);
            $this->getProcessor()->getTypeModel()->setVirtualChannelAttributes(array(), false);
        }
    }

    protected function execute() {}

    //########################################
}