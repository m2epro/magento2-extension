<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template;

class Description extends \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Product\Template
{
    protected $newAsin = false;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('amazon/listing/product/template/description.phtml');
    }

    //########################################

    protected function _beforeToHtml()
    {
        if ($this->isNewAsin()) {
            $helpBlock = $this->createBlock('HelpBlock')->setData([
//                'title' => $this->__('Assign Description Policy for New ASIN/ISBN Creation'),
                'content' => $this->__('
    For New ASIN/ISBN Creation you should select a prepared Description Policy,
    where New ASIN/ISBN Creation feature is obviously <strong>Enabled</strong>.<br/>
    If Description Policy cannot be assigned you will see a reason why it cannot become a base for
    New ASIN/ISBN Creation in <strong>Status/Reason</strong> Column.<br/><br/>

    <strong>Note:</strong> you can always add new Description Policy by pressing Add New
    Description Policy Button.<br/><br/>
    More detailed information about ability to work with this Page you can find
    <a href="%url%" target="_blank" class="external-link">here</a>.',
                    $this->getHelper('Module\Support')->getDocumentationArticleUrl('x/uYgVAQ')
                )
            ]);
        } else {
            $helpBlock = $this->createBlock('HelpBlock')->setData([
//                'title' => $this->__('Description Policy Assigning'),
                'content' => $this->__('
    Description Policy is using to update Amazon Product Information, such as Title, Images, etc.
    It can be assigned to already existed Products.<br/><br/>

    <strong>Note:</strong> To create New ASIN/ISBN you should not only assign
    Description Policy to your Products, but provide Settings for New ASIN/ISBN Creation.
    You can do it in two ways:<br/>
    <ul class="list">
    <li>using an <strong>Assign Settings for New ASIN/ISBN</strong>
    Option in Actions bulk at the top of the Grid;</li>
    <li>clicking on a Plus Icon in <strong>ASIN/ISBN Column</strong>
    of a Grid and selecting an <strong>Assign Settings for New ASIN/ISBN</strong> Option in an opened pop-up.</li>
    </ul>'
                )
            ]);
        }

        $this->setChild('help_block', $helpBlock);

        return parent::_beforeToHtml();
    }

    //########################################

    /**
     * @return boolean
     */
    public function isNewAsin()
    {
        return $this->newAsin;
    }

    /**
     * @param boolean $newAsin
     */
    public function setNewAsin($newAsin)
    {
        $this->newAsin = $newAsin;
    }

    //########################################
}