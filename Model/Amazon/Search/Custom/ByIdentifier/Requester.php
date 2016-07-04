<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search\Custom\ByIdentifier;

class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Search\ByIdentifier\ItemsRequester
{
    //########################################

    protected function getQuery()
    {
        return $this->params['query'];
    }

    protected function getQueryType()
    {
        return $this->params['query_type'];
    }

    protected function getVariationBadParentModifyChildToSimple()
    {
        return $this->params['variation_bad_parent_modify_child_to_simple'];
    }

    //########################################

    protected function getRequestData()
    {
        return array_merge(
            parent::getRequestData(),
            ['only_realtime' => true]
        );
    }

    //########################################
}