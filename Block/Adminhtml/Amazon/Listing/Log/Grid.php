<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Log;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Listing\Log\Grid
{
    //########################################

    protected function getComponentMode()
    {
        return \Ess\M2ePro\Helper\Component\Amazon::NICK;
    }

    //########################################

    protected function getActionTitles()
    {
        $allActions = $this->activeRecordFactory->getObject('Listing\Log')->getActionsTitles();

        $excludeActions = array(
//            \Ess\M2ePro\Model\Listing\Log::ACTION_TRANSLATE_PRODUCT => '',
            \Ess\M2ePro\Model\Listing\Log::ACTION_NEW_SKU_PRODUCT_ON_COMPONENT => ''
        );

        return array_diff_key($allActions, $excludeActions);
    }

    //########################################

    public function callbackColumnListingTitleID($value, $row, $column, $isExport)
    {
        if (strlen($value) > 50) {
            $value = substr($value, 0, 50) . '...';
        }

        $value = $this->getHelper('Data')->escapeHtml($value);

        if ($row->getData('listing_id')) {

            $url = $this->getUrl(
                '*/adminhtml_amazon_listing/view',
                array('id' => $row->getData('listing_id'))
            );

            $value = '<a target="_blank" href="'.$url.'">' .
                $value .
                '</a><br/>ID: '.$row->getData('listing_id');
        }

        return $value;
    }

    //########################################
}