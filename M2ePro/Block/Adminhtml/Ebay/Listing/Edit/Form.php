<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Edit\Form
 */
class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingTemplateEditForm');
        // ---------------------------------------
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'id'    => 'edit_form',
                'action'  => $this->getUrl('*/ebay_template/save'),
                'method' => 'post',
                'enctype' => 'multipart/form-data'
            ]
        ]);

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################

    protected function _toHtml()
    {
        if ($this->getRequest()->getParam('step')) {
            $breadcrumb = $this->createBlock('Ebay_Listing_Create_Breadcrumb');
            $breadcrumb->setSelectedStep((int)$this->getRequest()->getParam('step', 2));

            return $breadcrumb->_toHtml() . parent::_toHtml();
        }

        return parent::_toHtml();
    }

    //########################################
}
