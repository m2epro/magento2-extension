<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add;

class SaveProductsToSessionAndGetInfo extends \Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Add
{
    //########################################

    public function execute()
    {
        $tempSession = $this->getSessionValue('source_categories');
        $all = !isset($tempSession['products_ids']) ? array() : $tempSession['products_ids'];

        $checked = $this->getRequest()->getParam('checked_ids');
        $initial = $this->getRequest()->getParam('initial_checked_ids');

        $checked = explode(',',$checked);
        $initial = explode(',',$initial);

        $initial = array_values(array_unique(array_merge($initial,$checked)));
        $all     = array_values(array_unique(array_merge($all,$initial)));

        $all = array_flip($all);

        foreach (array_diff($initial,$checked) as $id) {
            unset($all[$id]);
        }

        $tempSession['products_ids'] = array_values(array_filter(array_flip($all)));
        $this->setSessionValue('source_categories', $tempSession);

        // ---------------------------------------

        $this->_forward('getTreeInfo');
    }

    //########################################
}