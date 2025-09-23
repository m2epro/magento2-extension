<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\View\Separated;

use Ess\M2ePro\Block\Adminhtml\Log\Listing\View;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\View\Separated\AbstractGrid
 */
abstract class AbstractGrid extends \Ess\M2ePro\Block\Adminhtml\Log\Listing\Product\AbstractGrid
{
    //########################################

    protected function getViewMode()
    {
        return View\Switcher::VIEW_MODE_SEPARATED;
    }

    // ---------------------------------------

    protected function _prepareCollection()
    {
        $collection = $this->activeRecordFactory->getObject('Listing\Log')->getCollection();

        $this->applyFilters($collection);

        $isNeedCombine = $this->isNeedCombineMessages();

        if ($isNeedCombine) {
            $collection->getSelect()->columns(
                ['greatest_create_date' => new \Zend_Db_Expr('MAX(main_table.create_date)')]
            );
            $collection->getSelect()->group(['main_table.listing_product_id', 'main_table.description']);
        } else {
            $collection->getSelect()->columns(
                ['greatest_create_date' => 'main_table.create_date']
            );
        }

        $this->setCollection($collection);

        $result = parent::_prepareCollection();

        if ($isNeedCombine) {
            $this->prepareMessageCount($collection);
        }

        return $result;
    }

    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->getColumn('create_date')
             ->setData('filter_index', 'greatest_create_date')
             ->setData('index', 'greatest_create_date');

        return $this;
    }

    //########################################
}
