<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Edit;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Edit\Form
 */
class Form extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractForm
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingEditForm');
        // ---------------------------------------
    }

    protected function _prepareForm()
    {
        $form = $this->_formFactory->create([
            'data' => [
                'id'    => 'edit_form',
                'action'  => $this->getUrl('*/amazon_listing/save'),
                'method' => 'post',
                'enctype' => 'multipart/form-data'
            ]
        ]);

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }

    //########################################
}
