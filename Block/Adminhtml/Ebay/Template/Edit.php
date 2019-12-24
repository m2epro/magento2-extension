<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Edit
 */
class Edit extends \Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer
{

    public function _construct()
    {
        parent::_construct();
        $this->_controller = 'adminhtml_ebay_template';
        $this->_mode = 'edit';

        // ---------------------------------------
        $nick = $this->getTemplateNick();
        $template = $this->getHelper('Data\GlobalData')->getValue("ebay_template_{$nick}");
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        $this->buttonList->remove('save');
        // ---------------------------------------

        // ---------------------------------------

        $isSaveAndClose = (bool)$this->getRequest()->getParam('close_on_save', false);

        // ---------------------------------------
        if ($template->getId() && !$isSaveAndClose) {
            $duplicateHeaderText = $this->getHelper('Data')->escapeJs(
                $this->__('Add %template_name% Policy', $this->getTemplateName())
            );

            $onclickHandler = $nick == \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION
                ? 'EbayTemplateDescriptionObj'
                : 'EbayTemplateEditObj';

            $this->buttonList->add('duplicate', [
                'label'     => $this->__('Duplicate'),
                'onclick'   => $onclickHandler.'.duplicateClick(
                    \'ebay-template\', \''.$duplicateHeaderText.'\', \''.$nick.'\'
                )',
                'class'     => 'add M2ePro_duplicate_button primary'
            ]);

            $url = $this->getUrl('*/ebay_template/delete');
            $this->buttonList->add('delete', [
                'label'     => $this->__('Delete'),
                'onclick'   => 'EbayTemplateEditObj.deleteClick(\'' . $url . '\')',
                'class'     => 'delete M2ePro_delete_button primary'
            ]);
        }
        // ---------------------------------------

        $saveConfirmation = '';
        if ($template->getId()) {
            $saveConfirmation = $this->getHelper('Data')->escapeJs(
                $this->__(
                    '<br/><b>Note:</b> All changes you have made will be automatically
                    applied to all M2E Pro Listings where this Policy is used.'
                )
            );
        }

        // ---------------------------------------

        $backUrl = $this->getHelper('Data')->makeBackUrlParam('edit');
        $url = $this->getUrl('*/ebay_template/save', [
            'back' => $backUrl,
            'wizard' => $this->getRequest()->getParam('wizard'),
            'close_on_save' => $this->getRequest()->getParam('close_on_save'),
        ]);

        if ($isSaveAndClose) {
            $this->removeButton('back');

            $saveButtons = [
                'id' => 'save_and_close',
                'label' => $this->__('Save And Close'),
                'class' => 'add',
                'button_class' => '',
                'onclick' => "EbayTemplateEditObj.saveAndCloseClick('{$url}', '{$saveConfirmation}')",
                'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton',
                'options' => [
                    'save' => [
                        'label' => $this->__('Save And Continue Edit'),
                        'onclick' =>
                            "EbayTemplateEditObj.saveAndEditClick('{$url}', '', '{$saveConfirmation}', '{$nick}');"
                    ]
                ],
            ];
        } else {
            $saveAndBackUrl = $this->getUrl('*/ebay_template/save', [
                'back' => $this->getHelper('Data')->makeBackUrlParam('list')
            ]);

            $saveButtons = [
                'id' => 'save_and_continue',
                'label' => $this->__('Save And Continue Edit'),
                'class' => 'add',
                'button_class' => '',
                'onclick' =>
                    "EbayTemplateEditObj.saveAndEditClick('{$url}', '', '{$saveConfirmation}', '{$nick}');",
                'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton',
                'options' => [
                    'save' => [
                        'label' => $this->__('Save And Back'),
                        'onclick' =>
                            "EbayTemplateEditObj.saveClick('{$saveAndBackUrl}', '{$saveConfirmation}', '{$nick}');",
                    ]
                ],
            ];
        }

        $this->addButton('save_buttons', $saveButtons);
    }

    //########################################

    public function getTemplateNick()
    {
        if (!isset($this->_data['template_nick'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Policy nick is not set.');
        }

        return $this->_data['template_nick'];
    }

    public function getTemplateObject()
    {
        $nick = $this->getTemplateNick();
        $template = $this->getHelper('Data\GlobalData')->getValue("ebay_template_{$nick}");

        return $template;
    }

    //########################################

    protected function getTemplateName()
    {
        $title = '';

        switch ($this->getTemplateNick()) {
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_PAYMENT:
                $title = $this->__('Payment');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING:
                $title = $this->__('Shipping');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY:
                $title = $this->__('Return');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT:
                $title = $this->__('Selling');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION:
                $title = $this->__('Description');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION:
                $title = $this->__('Synchronization');
                break;
        }

        return $title;
    }

    //########################################
}
