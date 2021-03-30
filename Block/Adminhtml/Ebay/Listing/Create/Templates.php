<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Create;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Create\Templates
 */
class Templates extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        $this->setId('ebayListingCreateTemplates');
        $this->_controller = 'adminhtml_ebay_listing_create';
        $this->_mode = 'templates';

        $this->_headerText = $this->__('Creating A New M2E Pro Listing');

        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');

        $url = $this->getUrl(
            '*/ebay_listing_create/index',
            ['_current' => true, 'step' => 1]
        );
        $this->addButton(
            'back',
            [
                'label'   => $this->__('Previous Step'),
                'onclick' => 'CommonObj.backClick(\'' . $url . '\')',
                'class'   => 'back'
            ]
        );

        $nextStepBtnText = 'Next Step';

        $sessionData = $this->getHelper('Data_Session')->getValue(
            \Ess\M2ePro\Model\Ebay\Listing::CREATE_LISTING_SESSION_DATA
        );
        if (isset($sessionData['creation_mode']) && $sessionData['creation_mode'] ===
            \Ess\M2ePro\Helper\View::LISTING_CREATION_MODE_LISTING_ONLY
        ) {
            $nextStepBtnText = 'Complete';
        }

        $url = $this->getUrl(
            '*/ebay_listing_create/index',
            ['_current' => true]
        );

        $this->addButton(
            'save',
            [
                'label'   => $this->__($nextStepBtnText),
                'onclick' => 'CommonObj.saveClick(\'' . $url . '\')',
                'class'   => 'action-primary forward'
            ]
        );
    }

    //########################################

    protected function _toHtml()
    {
        $breadcrumb = $this->createBlock('Ebay_Listing_Create_Breadcrumb');
        $breadcrumb->setSelectedStep(2);

        $helpBlock = $this->createBlock('HelpBlock');
        $helpBlock->addData(
            [
                'content' => $this->__(
                    <<<HTML
<p>In this Section, you set the payment methods you accept, the shipping methods you offer, and whether you accept 
returns. For that, select <b>Payment</b>, <b>Shipping</b>, and <b>Return</b> Policies for the Listing.</p>
<p>Also, you can choose the right listing format, provide a competitive price for your Items, set the detailed 
description for products to attract more buyers. For that, select <b>Selling</b> and <b>Description</b> 
Policies for the Listing.</p>
<p>You can set the preferences on how to synchronize your Items with Magento Catalog data. The rules can be defined in 
<b>Synchronization</b> policy.</p>
<p>More details in <a href="%url%" target="_blank">our documentation</a>.</p>
HTML
                    ,
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/7QItAQ ')
                ),
                'style'   => 'margin-top: 30px'
            ]
        );

        return
            $breadcrumb->_toHtml() .
            '<div id="progress_bar"></div>' .
            $helpBlock->toHtml() .
            '<div id="content_container">' . parent::_toHtml() . '</div>';
    }

    //########################################
}
