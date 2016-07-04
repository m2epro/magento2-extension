<?php

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template;

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

            $this->buttonList->add('duplicate', array(
                'label'     => $this->__('Duplicate'),
                'onclick'   => $onclickHandler.'.duplicateClick(
                    \'ebay-template\', \''.$duplicateHeaderText.'\', \''.$nick.'\'
                )',
                'class'     => 'add M2ePro_duplicate_button primary'
            ));

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
        $saveButtonsProps = [];

        $url = $this->getUrl('*/ebay_template/save');
        if (!$isSaveAndClose) {
            $saveButtonsProps['save'] = [
                'label' => $this->__('Save And Back'),
                'onclick' => 'EbayTemplateEditObj.saveClick('
                    . '\'' . $url . '\','
                    . '\'' . $saveConfirmation . '\','
                    . '\'' . $nick . '\''
                    . ')',
            ];
        }

        if ($isSaveAndClose) {
            $saveButtonsProps['save'] = [
                'label' => $this->__('Save And Close'),
                'onclick' => "EbayTemplateEditObj.saveAndCloseClick('$url')"
            ];
            $this->removeButton('back');
        }

        $backUrl = $this->getHelper('Data')->makeBackUrlParam('edit', []);
        $url = $this->getUrl('*/ebay_template/save', ['back' => $backUrl]);

        $saveButtons = [
            'id' => 'save_and_continue',
            'label' => $this->__('Save And Continue Edit'),
            'class' => 'add',
            'button_class' => '',
            'onclick' => 'EbayTemplateEditObj.saveAndEditClick('
                . '\'' . $url . '\','
                . '\'\','
                . '\'' . $saveConfirmation . '\','
                . '\'' . $nick . '\''
                . ')',
            'class_name' => 'Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton',
            'options' => $saveButtonsProps,
        ];

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
                $title = $this->__('Price, Quantity and Format');
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