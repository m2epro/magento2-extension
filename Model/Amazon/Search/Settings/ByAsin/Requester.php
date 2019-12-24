<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Search\Settings\ByAsin;

/**
 * Class \Ess\M2ePro\Model\Amazon\Search\Settings\ByAsin\Requester
 */
class Requester extends \Ess\M2ePro\Model\Amazon\Connector\Search\ByAsin\ItemsRequester
{
    // ########################################

    protected function getProcessingRunnerModelName()
    {
        return 'Amazon_Search_Settings_ProcessingRunner';
    }

    protected function getResponserParams()
    {
        return array_merge(
            parent::getResponserParams(),
            ['type' => 'asin', 'value' => $this->getQuery()]
        );
    }

    // ########################################

    protected function getQuery()
    {
        return $this->params['query'];
    }

    protected function getVariationBadParentModifyChildToSimple()
    {
        return $this->params['variation_bad_parent_modify_child_to_simple'];
    }

    // ########################################
}
