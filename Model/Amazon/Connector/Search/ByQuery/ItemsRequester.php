<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Search\ByQuery;

abstract class ItemsRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    // ########################################

    public function getCommand()
    {
        return array('product','search','byQuery');
    }

    // ########################################

    abstract protected function getQuery();

    abstract protected function getVariationBadParentModifyChildToSimple();

    // ########################################

    protected function getRequestData()
    {
        return array(
            'query' => $this->getQuery(),
            'variation_bad_parent_modify_child_to_simple' => $this->getVariationBadParentModifyChildToSimple()
        );
    }

    // ########################################
}