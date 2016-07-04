<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Developers\Tabs;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

class PerformanceNotes extends AbstractBlock
{
    //########################################

    protected function _toHtml()
    {
        $helpBlock = $this->createBlock('HelpBlock', '', ['data' => [
            'no_collapse' => true,
            'no_hide' => true,
            'content' => $this->__(
                <<<HTML
                <p>M2E Pro is one of the multi-functional and complex modules developed for Magento.
            It brings a great number of automatic actions. For example, Quantity change of one Magento Product
            can lead to multiple updates of this value on different channels (eBay/Amazon) and for 
            different copies of the Item (e.g. on different Marketplaces). So, 1 change may trigger 
            several calculations and several actions.</p><br>
            
            <p>Thus, if your Magento Catalog contains a large number of Products and you update a variety 
            of Products on all of the available Platforms (eBay, Amazon, etc.), Performance aspect becomes rather
            important and vital.</p><br>

            <p>M2E Pro drew up a list of points which could help to optimize the Performance. The main of points 
            are the following:</p>

            <ul>
            <li><p><strong>Always keep your M2E Pro updated</strong></p>
            <p>Unlike many other multi-channel providers, we release updates for our software quite regularly and 
            it’s the customer’s responsibility to ensure that their system is up to date. Each updated software 
            version contains various fixes, new features as well as overall system enhancement.</p></li>
            <li><p><strong>Disable options/features that are not in use</strong></p>
            <p>Following the initial configuration, please review your M2E Pro config and disable those options, 
            channels and features which you do not intend to use.</p></li>
            <li><p><strong>Use Conditional Revise Features</strong></p>
            <p>M2E Pro has “Conditional Revise” options implemented for both “Quantity Revise” and “Price Revise” 
            in Synchronization Policy. <br>
            The main purpose of the “Conditional Revise” is to limit a number of the performed “Revise” actions.
            It contributes to M2E Pro Performance improvement.</p></li>
            <li><p><strong>Disable Product Description Information</strong></p>
            <p>In most of the cases there is no need to continuously update description-related details of the 
            Product such as Title, Subtitle, Images or Product Description in real-time mode. 
            To improve system Performance, M2E Pro recommends disabling these options in Synchronization 
            Policy.</p></li>
            <li><p><strong>Duplication of the same Product in M2E Pro Listing</strong></p>
            <p>Some sellers have more than one duplicate of the same Product (from Magento Catalog)
            in M2E Pro Listing as well as listed on the same marketplace. This “bad practice” always results
            in multiple revisions of every Product copy which makes synchronization process 
            very time-consuming.</p></li>
            <li><p><strong>Maintain more than one M2E Pro Listing</strong></p>
            <p>You can create a number of M2E Pro Listings and add Products to them. If you add all your Products 
            to one M2E Pro Listing, this will make your interface work more slowly.</p></li>
            </ul><br>
            
            <p>Full detailed information you can find <a href="%url1%" target="_blank">here</a></p>
HTML
                ,
                $this->getHelper('Module\Support')->getDocumentationUrl(NULL, NULL, 'x/z4QVAQ')
            )
        ]]);
        return $helpBlock->toHtml() . parent::_toHtml();
    }

    //########################################
}