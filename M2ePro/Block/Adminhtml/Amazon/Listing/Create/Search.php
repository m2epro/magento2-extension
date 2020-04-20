<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Create\Search
 */
class Search extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingCreateStepThree');
        $this->_controller = 'adminhtml_amazon_listing_create';
        $this->_mode = 'search';
        // ---------------------------------------

        // Set header text
        // ---------------------------------------
        $this->_headerText = $this->__("Creating A New Amazon M2E Pro Listing");
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->removeButton('back');
        $this->removeButton('reset');
        $this->removeButton('delete');
        $this->removeButton('add');
        $this->removeButton('save');
        $this->removeButton('edit');
        // ---------------------------------------

        if (!$this->getRequest()->getParam('exclude_control_buttons')) {
            // ---------------------------------------
            $url = $this->getUrl('*/amazon_listing_create/index', [
                '_current' => true,
                'step' => '2'
            ]);
            $this->addButton('back', [
                'label'     => $this->__('Previous Step'),
                'onclick'   => 'CommonObj.backClick(\'' . $url . '\')',
                'class'     => 'action-primary back'
            ]);
            // ---------------------------------------

            // ---------------------------------------
            $url = $this->getUrl('*/amazon_listing_create/index', [
                '_current' => true
            ]);
            $this->addButton('save_and_next', [
                'label'     => $this->__('Next Step'),
                'onclick'   => 'CommonObj.saveClick(\'' . $url . '\')',
                'class'     => 'action-primary forward'
            ]);
            // ---------------------------------------
        }
    }

    //########################################

    protected function _toHtml()
    {
        $breadcrumb = $this->createBlock('Amazon_Listing_Create_Breadcrumb')
            ->setSelectedStep((int)$this->getRequest()->getParam('step', 1));

        $helpBlock = $this->createBlock('HelpBlock')->setData([
            'content' => $this->__(
                'In this Section you can specify the sources from which the values for ASIN/ISBN and
                UPC/EAN will be taken in case you have those for your Items. <br/><br/>
                These Settings will be used in two cases:

                <ul class="list">
                    <li>at the time of using Automatic ASIN/ISBN Search;</li>
                    <li>at the time of using “List” Action.</li>
                </ul>

                Using these Settings means the Search of existing Amazon Products and the process of
                linking Magento Product with found Amazon Product. <br/><br/>

                During the process of Search, Settings values are used according to the following logic:

                <ul class="list">
                    <li>the Product is searched by ASIN/ISBN parameter. (if specified);</li>
                    <li>if no result by ASIN/ISBN parameter, then UPC/EAN search is performed. (if specified);</li>
                    <li>if no result by UPC/EAN parameter, then additional search by Magento Product Name is performed.
                    (if enabled).</li>
                </ul>
                <br/>
                More detailed information you can find
                <a href="%url%" target="_blank" class="external-link">here</a>.',
                $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/YQItAQ')
            )
        ]);

        return
            $breadcrumb->toHtml() .
            $helpBlock->toHtml() .
            parent::_toHtml();
    }

    //########################################
}
