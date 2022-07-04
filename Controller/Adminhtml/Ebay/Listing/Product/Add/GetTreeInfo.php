<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add\GetTreeInfo
 */
class GetTreeInfo extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add
{

    public function execute()
    {
        $tempSession = $this->getSessionValue('source_categories');
        $tempSession['products_ids'] = !isset($tempSession['products_ids']) ? [] : $tempSession['products_ids'];

        /** @var \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\Category\Tree $treeBlock */
        $treeBlock = $this->getLayout()
                          ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\Category\Tree::class);
        $treeBlock->setSelectedIds($tempSession['products_ids']);

        $this->setAjaxContent($treeBlock->getInfoJson(), false);

        return $this->getResult();
    }
}
