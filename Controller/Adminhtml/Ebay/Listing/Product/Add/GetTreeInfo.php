<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add;

class GetTreeInfo extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Add
{

    public function execute() {
        $tempSession = $this->getSessionValue('source_categories');
        $tempSession['products_ids'] = !isset($tempSession['products_ids']) ? array() : $tempSession['products_ids'];

        /* @var $treeBlock \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Product\Add\Category\Tree */
        $treeBlock = $this->createBlock('Ebay\Listing\Product\Add\Category\Tree');
        $treeBlock->setSelectedIds($tempSession['products_ids']);

        $this->setAjaxContent($treeBlock->getInfoJson(), false);

        return $this->getResult();
    }

}