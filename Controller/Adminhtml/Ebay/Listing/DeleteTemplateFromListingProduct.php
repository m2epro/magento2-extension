<?php
/**
 * Created by PhpStorm.
 * User: myown
 * Date: 23.06.16
 * Time: 14:49
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;


class DeleteTemplateFromListingProduct extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    protected $templateManager;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Ebay\Template\Manager $templateManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    )
    {
        $this->templateManager = $templateManager;
        parent::__construct($ebayFactory, $context);
    }

    //########################################

    public function execute()
    {
        // ---------------------------------------
        $id = (int)$this->getRequest()->getParam('id');
        $nick = $this->getRequest()->getParam('nick');
        // ---------------------------------------

        $listingProduct = $this->ebayFactory->getObjectLoaded('Listing\Product', $id, NULL, false);
        $allTemplates = $this->templateManager->getAllTemplates();

        if (is_null($listingProduct) || !in_array($nick, $allTemplates)) {
            $this->setAjaxContent(json_encode([
                'success' => false
            ]), false);
            return $this->getResult();
        }
        
        $templateMode = $listingProduct->getChildObject()->getData('template_'.$nick.'_mode');
        
        if ($templateMode == \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_PARENT) {
            $this->setAjaxContent(json_encode([
                'success' => true
            ]), false);
            return $this->getResult();
        }

        $listingProduct->getChildObject()->setData(
            'template_'.$nick.'_mode', \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_PARENT
        );
        
        if ($templateMode == \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_CUSTOM) {
            
            $listingProduct->getChildObject()->setData('template_'.$nick.'_custom_id', NULL);
            
        } else if ($templateMode == \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_TEMPLATE) {
            
            $listingProduct->getChildObject()->setData('template_'.$nick.'_id', NULL);
        }
        
        $listingProduct->save();

        $this->setAjaxContent(json_encode([
            'success' => true
        ]), false);
        return $this->getResult();
    }

    //########################################
}