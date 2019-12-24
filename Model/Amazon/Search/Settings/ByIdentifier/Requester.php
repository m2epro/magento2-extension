<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search\Settings\ByIdentifier;

/**
 * Class \Ess\M2ePro\Model\Amazon\Search\Settings\ByIdentifier\Requester
 */
class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Search\ByIdentifier\ItemsRequester
{
    // ########################################

    protected function getResponserRunnerModelName()
    {
        return 'Amazon\Search\Settings\ProcessingRunner';
    }

    protected function getResponserParams()
    {
        return array_merge(
            parent::getResponserParams(),
            ['type' => $this->getQueryType(), 'value' => $this->getQuery()]
        );
    }

    // ########################################

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

    // ########################################
}
