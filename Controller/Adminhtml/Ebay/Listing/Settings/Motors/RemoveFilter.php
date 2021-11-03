<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors\RemoveFilter
 */
class RemoveFilter extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    //########################################

    public function execute()
    {
        $filtersIds = $this->getRequest()->getParam('filters_ids');

        if (!is_array($filtersIds)) {
            $filtersIds = explode(',', $filtersIds);
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Ebay\Motor\Filter\Collection $filters */
        $filters = $this->activeRecordFactory->getObject('Ebay_Motor_Filter')->getCollection()
            ->addFieldToFilter('id', ['in' => $filtersIds]);

        foreach ($filters->getItems() as $filter) {
            $filter->delete();
        }

        $this->setAjaxContent(0, false);

        return $this->getResult();
    }

    //########################################
}
