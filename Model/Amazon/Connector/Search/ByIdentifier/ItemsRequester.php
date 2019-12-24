<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Connector\Search\ByIdentifier;

/**
 * Class \Ess\M2ePro\Model\Amazon\Connector\Search\ByIdentifier\ItemsRequester
 */
abstract class ItemsRequester extends \Ess\M2ePro\Model\Amazon\Connector\Command\Pending\Requester
{
    // ########################################

    public function getCommand()
    {
        return ['product','search','byIdentifier'];
    }

    // ########################################

    abstract protected function getQuery();

    abstract protected function getQueryType();

    abstract protected function getVariationBadParentModifyChildToSimple();

    // ########################################

    protected function getRequestData()
    {
        return [
            'item' => $this->getQuery(),
            'id_type' => $this->getQueryType(),
            'variation_bad_parent_modify_child_to_simple' => $this->getVariationBadParentModifyChildToSimple()
        ];
    }

    // ########################################
}
