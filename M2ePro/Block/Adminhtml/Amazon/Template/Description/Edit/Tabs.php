<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Edit;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Template\Description\Edit\Tabs
 */
class Tabs extends \Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs
{
    protected $_groups = ['configuration'];

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonTemplateDescriptionEditTabs');
        // ---------------------------------------

        $this->setDestElementId('tabs_edit_form_data');
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->addTab('general', [
            'label'   => $this->__('Main'),
            'title'   => $this->__('Main'),
            'content' => $this->createBlock('Amazon_Template_Description_Edit_Tabs_General')
                              ->toHtml(),
        ]);

        $this->addTab('definition', [
            'label'   => $this->__('Definition'),
            'title'   => $this->__('Definition'),
            'content' => $this->createBlock('Amazon_Template_Description_Edit_Tabs_Definition')
                              ->toHtml(),
        ]);

        $this->addTab('specifics', [
            'label'   => $this->__('Specifics'),
            'title'   => $this->__('Specifics'),
            'content' => $this->createBlock('Amazon_Template_Description_Edit_Tabs_Specifics')
                              ->toHtml(),
        ]);

        $this->setActiveTab($this->getRequest()->getParam('tab', 'general'));

        return parent::_prepareLayout();
    }

    //########################################

    public function _beforeToHtml()
    {
        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Model\Amazon\Template\Description::class)
        );

        $this->jsPhp->addConstants(
            $this->getHelper('Data')->getClassConstants(\Ess\M2ePro\Helper\Component\Amazon::class)
        );

        $this->jsUrl->addUrls($this->getHelper('Data')->getControllerActions('Amazon_Template_Description'));
        $this->jsUrl->addUrls([
            'formSubmit'    => $this->getUrl(
                '*/amazon_template_description/save',
                ['_current' => true]
            ),
            'formSubmitNew' => $this->getUrl('*/amazon_template_description/save'),
            'deleteAction'  => $this->getUrl(
                '*/amazon_template_description/delete',
                ['_current' => true]
            ),

            'amazon_marketplace/index' => $this->getUrl('*/amazon_marketplace/index'),
            'm2epro_skin_url' => $this->getViewFileUrl('Ess_M2ePro')
        ]);

        return parent::_beforeToHtml();
    }

    //########################################
}
