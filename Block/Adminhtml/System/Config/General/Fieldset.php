<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\General;

class Fieldset extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    /**
     * Return header title part of html for fieldset
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getHeaderTitleHtml($element)
    {
        return '<a id="' .
        $element->getHtmlId() .
        '-head" href="#' .
        $element->getHtmlId() .
        '-link" onclick="return false;">' . $element->getLegend() . '</a>';
    }

    protected function _getHeaderHtml($element)
    {
        return $this->getIntegrationHelpBlockHtml($element->getHtmlId()) . parent::_getHeaderHtml($element);
    }

    /**
     * Get collapsed state on-load
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return false
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _isCollapseState($element)
    {
        return true;
    }

    protected function getIntegrationHelpBlockHtml($htmlId)
    {
        if (strpos($htmlId, 'ebay') !== false) {

            $content = __(<<<HTML
            <p>You can enable/disable eBay Integration.</p><br>
            
            <p>Once the Integration is disabled, its menu is not available in Magento panel.
            Automatic data synchronization for eBay channel will not be running 
            (even if you did not remove the data from M2E Pro).</p>
HTML
            );

        } else if (strpos($htmlId, 'amazon') !== false) {

            $content = __(<<<HTML
            <p>You can enable/disable Amazon Integration.</p><br>
            
            <p>Once the Integration is disabled, its menu is not available in Magento panel.
            Automatic data synchronization for eBay channel will not be running 
            (even if you did not remove the data from M2E Pro).</p>
HTML
            );

        } else {

            $content = __(<<<HTML
            <p>Currently, M2E Pro Team is working on the migration of Rakuten.com
            Integration from Magento v 1.x environment to Magento v 2.x.</p><br>
            
            <p>Rakuten.co.uk and Rakuten.de Integrations are either being in the phase of
            implementation or have been scheduled for developement in the shortest possible time.</p>
HTML
            );
        }

        $helpBlock = $this->getLayout()->createBlock('Ess\M2ePro\Block\Adminhtml\HelpBlock', '', ['data' => [
            'no_collapse' => true,
            'no_hide' => true,
            'content' => $content
        ]]);

        $css = "<style>
                .scope-label { visibility: hidden }
                .entry-edit-head-link + a:before { content: '' !important; }
                </style>";

        $script = <<<HTML
        <script>
            require(['M2ePro/Common','M2ePro/Plugin/BlockNotice', 'M2ePro/General/PhpFunctions'], function() {
                window.BlockNoticeObj = new BlockNotice();
                BlockNoticeObj.init();
            });
        </script>
HTML;

        return $css . $helpBlock->toHtml() . $script;
    }
}