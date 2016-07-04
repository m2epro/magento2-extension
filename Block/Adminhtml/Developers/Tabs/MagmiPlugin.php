<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Developers\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class MagmiPlugin extends AbstractBlock
{
    //########################################

    protected function _toHtml()
    {
        $text = $this->__(
            <<<HTML
            <p>M2E Pro is developed to work based on standard Magento functionality.
            One of the main aspects of its work is a dynamic event detection of the Product Data
            changes - Price, Quantity, Images, Attributes, etc.</p><br>
            <p>However, if Product Data is changed via Magmi Import Tool, M2E Pro will not catch all of the changes.
            It is related to the fact that Magmi Import Tool (along with many other similar tools) makes 
            changes directly in Magento Database without any Core Magento Functions involved. Inability to track 
            the events of Product Data change leads to inability to deliver these changes to the channels 
            (eBay, Amazon, etc.).</p><br>
            <p>M2E Pro Developers created a solution for such case - Plugin for Magmi Import Tool. 
            Once it is installed into the Magmi Import Tool, it allows identifying which Products were changed 
            and updating these changes on the channels.</p><br>
            <p><strong>Note:</strong> It is strongly recommended to install M2E Pro Plugin for Magmi Import Tool
            to prevent data re-synchronization between eBay/Amazon and Magento values.</p>
            <p>More detailed information about the Plugin, i.e. how to install, update and use it, you can find
            <a href="%url1%" target="_blank">here</a></p><br>
            <p>Please, remember that, along with the Plugin for Magmi Import Tool, the predefined
            <strong>M2E Pro Models</strong> could be used by developers to modify the code in case the 
            Product Changes are implemented directly into the Magento Database via an external script/tool. 
            More detailed information can be found <a href="%url2%" target="_blank">here</a></p>
            
HTML
            ,
            $this->getHelper('Module\Support')->getDocumentationUrl(NULL, NULL, 'x/yIQVAQ'),
            $this->getHelper('Module\Support')->getDocumentationUrl(NULL, NULL, 'x/xYQVAQ')
        );
        return "<div id='text-block'><div>{$text}</div></div>" . parent::_toHtml();
    }

    //########################################
}