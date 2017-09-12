<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\System\Config\General;

class Fieldset extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    private $helperFactory = NULL;

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\View\Helper\Js $jsHelper,
        array $data = []
    )
    {
        $this->helperFactory = $helperFactory;
        parent::__construct($context, $authSession, $jsHelper, $data);
    }

    /**
     * Return header title part of html for fieldset
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
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
        $controlPanelUrl = $this->getUrl('m2epro/controlPanel');

        return $this->getIntegrationHelpBlockHtml($element->getHtmlId()) . parent::_getHeaderHtml($element) .
               '<script>require(["M2ePro/ControlPanel"], function() {
                    window.ControlPanelObj = new ControlPanel();
                    window.ControlPanelObj.setControlPanelUrl(\''.$controlPanelUrl.'\')
                })</script>';
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

        } elseif (strpos($htmlId, 'amazon') !== false) {

            $content = __(<<<HTML
            <p>You can enable/disable Amazon Integration.</p><br>

            <p>Once the Integration is disabled, its menu is not available in Magento panel.
            Automatic data synchronization for Amazon channel will not be running
            (even if you did not remove the data from M2E Pro).</p>
HTML
            );

        } elseif (strpos($htmlId, 'buy') !== false) {

            $content = __(<<<HTML
            <p>Currently, M2E Pro Team is working on the migration of Rakuten.com
            Integration from Magento v 1.x environment to Magento v 2.x.</p><br>

            <p>Rakuten.co.uk and Rakuten.de Integrations are either being in the phase of
            implementation or have been scheduled for developement in the shortest possible time.</p>
HTML
            );
        } elseif (strpos($htmlId, 'advanced') !== false) {

            $url = $this->helperFactory->getObject('Module\Support')->getDocumentationArticleUrl("x/EgA9AQ");
            $content = __(<<<HTML
            <p>This page contains additional functionality for M2E Pro Module’s management such as ability to
            enable/disable the Module and Automatic Synchronization in it,
            ability to start the <a href="{$url}" target="_blank">Migration Wizard</a>, etc.</p>
HTML
            );
        }

        $helpBlockHtml = '';
        if (!empty($content)) {
            $helpBlockHtml = $this->getLayout()->createBlock('Ess\M2ePro\Block\Adminhtml\HelpBlock', '', ['data' => [
                'no_collapse' => true,
                'no_hide' => true,
                'content' => $content
            ]])->toHtml();
        }

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

        return $css . $helpBlockHtml . $script;
    }
}