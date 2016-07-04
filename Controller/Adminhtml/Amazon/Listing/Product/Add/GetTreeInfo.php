<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

class GetTreeInfo extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $tempSession = $this->getSessionValue('source_categories');
        $tempSession['products_ids'] = !isset($tempSession['products_ids']) ? array() : $tempSession['products_ids'];

        /* @var $treeBlock \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Add\SourceMode\Category\Tree */
        $treeBlock = $this->createBlock('Amazon\Listing\Product\Add\SourceMode\Category\Tree', '', [
            'data' => [
                'tree_settings' => [
                    'show_products_amount' => true,
                    'hide_products_this_listing' => true
                ]
            ]
        ]);
        $treeBlock->setSelectedIds($tempSession['products_ids']);

        $this->setAjaxContent($treeBlock->getInfoJson(), false);
        
        return $this->getResult();
    }

    //########################################
}