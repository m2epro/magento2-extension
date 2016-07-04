<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\View;

use \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

abstract class Switcher extends AbstractBlock
{
    const NICK = 'default';
    const LABEL = 'Default';

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingViewModeSwitcher');
        // ---------------------------------------

        $this->setTemplate('Ess_M2ePro::listing/view/switcher.phtml');

        $this->setData('component_nick', self::NICK);
        $this->setData('component_label', self::LABEL);
    }

    protected function _toHtml()
    {

        $this->addData(array(
            'current_view_mode' => $this->getCurrentViewMode(),
            'route' => '*/*/view',
            'items' => $this->getMenuItems()
        ));

        $modeChangeLabel = $this->__('View Mode');
        $parentHtml = parent::_toHtml();

        $this->js->add(<<<JS
        
    require([
        'jquery',
        'prototype'
    ],function(jQuery) {
    
        var listingViewModeSwitcher = function() {
            var url = '{$this->getSwitchUrl()}';
            url = url.replace('%view_mode%', this.value);
            setLocation(url);
        };
    
        $('listing_view_mode_switcher').observe('change', listingViewModeSwitcher);
    });
    
JS
);

        return <<<HTML
<div id="listing_view_mode_switcher_container" 
     style="padding: 5px; position: absolute; left: 130px;">
    <b>{$modeChangeLabel} </b>{$parentHtml}
</div>
HTML;
    }

    protected function getMenuItems()
    {
        return array(
            array(
                'value' => $this->getComponentNick(),
                'label' => $this->__($this->getComponentLabel())
            ),
            array(
                'value' => 'settings',
                'label' => $this->__('Settings')
            ),
            array(
                'value' => 'magento',
                'label' => $this->__('Magento')
            )
        );
    }

    private function getCurrentViewMode()
    {
        if (!isset($this->_data['current_view_mode'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('View Mode is not set.');
        }

        return $this->_data['current_view_mode'];
    }

    //########################################

    public function getItems()
    {
        if (empty($this->_data['items']) || !is_array($this->_data['items'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Items are not set.');
        }

        return $this->_data['items'];
    }

    public function getRoute()
    {
        if (empty($this->_data['route'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Route is not set.');
        }

        return $this->_data['route'];
    }

    public function getSwitchUrl()
    {
        $params = array();

        if ($id = $this->getRequest()->getParam('id')) {
            $params['id'] = $id;
        }

        $params['view_mode'] = '%view_mode%';

        return $this->getUrl($this->getRoute(), $params);
    }

    //########################################
}