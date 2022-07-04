<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\View\Group\Filters;
use Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Motors\View\Group\Items;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors\GetGroupContentView
 */
class GetGroupContentView extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $groupId = $this->getRequest()->getParam('group_id');

        /** @var \Ess\M2ePro\Model\Ebay\Motor\Group $model */
        $model = $this->activeRecordFactory->getObjectLoaded('Ebay_Motor_Group', $groupId);

        if ($model->isModeItem()) {
            $block = $this->getLayout()->createBlock(Items::class);
        } else {
            $block = $this->getLayout()->createBlock(Filters::class);
        }

        $block->setGroupId($groupId);

        $this->setAjaxContent($block);

        return $this->getResult();
    }

    //########################################
}
