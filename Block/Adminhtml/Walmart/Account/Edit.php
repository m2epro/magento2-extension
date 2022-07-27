<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Account;

use Ess\M2ePro\Block\Adminhtml\Magento\Form\AbstractContainer;

class Edit extends AbstractContainer
{
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;
    /** @var \Ess\M2ePro\Helper\Module\Wizard */
    private $wizardHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Helper\Module\Wizard $wizardHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        array $data = []
    ) {
        $this->globalDataHelper = $globalDataHelper;
        $this->wizardHelper = $wizardHelper;
        parent::__construct($context, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartAccountEdit');
        $this->_controller = 'adminhtml_walmart_account';
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

        if ($this->wizardHelper->isActive(\Ess\M2ePro\Helper\View\Walmart::WIZARD_INSTALLATION_NICK)) {
            // ---------------------------------------
            $this->addButton(
                'save_and_continue',
                [
                    'label'   => $this->__('Save And Continue Edit'),
                    'onclick' => 'WalmartAccountObj.saveAndEditClick(\'\',\'walmartAccountEditTabs\')',
                    'class'   => 'action-primary'
                ]
            );
            // ---------------------------------------

            if ($this->getRequest()->getParam('id')) {
                // ---------------------------------------
                $url = $this->getUrl('*/walmart_account/new', ['wizard' => true]);
                $this->addButton(
                    'add_new_account',
                    [
                        'label'   => $this->__('Add New Account'),
                        'onclick' => 'setLocation(\'' . $url . '\')',
                        'class'   => 'action-primary'
                    ]
                );
                // ---------------------------------------
            }
        } else {
            if ((bool)$this->getRequest()->getParam('close_on_save', false)) {
                if ($this->getRequest()->getParam('id')) {
                    $this->addButton(
                        'save',
                        [
                            'label'   => $this->__('Save And Close'),
                            'onclick' => 'WalmartAccountObj.saveAndClose()',
                            'class'   => 'action-primary'
                        ]
                    );
                } else {
                    $this->addButton(
                        'save_and_continue',
                        [
                            'label'   => $this->__('Save And Continue Edit'),
                            'onclick' => 'WalmartAccountObj.saveAndEditClick(\'\',\'walmartAccountEditTabs\')',
                            'class'   => 'action-primary'
                        ]
                    );
                }

                return;
            }

            // ---------------------------------------
            $url = $this->getUrl('*/walmart_account/index');
            $this->addButton(
                'back',
                [
                    'label'   => $this->__('Back'),
                    'onclick' => 'WalmartAccountObj.backClick(\'' . $url . '\')',
                    'class'   => 'back'
                ]
            );
            // ---------------------------------------

            // ---------------------------------------
            if ($this->globalDataHelper->getValue('edit_account') &&
                $this->globalDataHelper->getValue('edit_account')->getId()
            ) {
                // ---------------------------------------
                $this->addButton(
                    'delete',
                    [
                        'label'   => $this->__('Delete'),
                        'onclick' => 'WalmartAccountObj.deleteClick()',
                        'class'   => 'delete M2ePro_delete_button primary'
                    ]
                );
                // ---------------------------------------
            }

            // ---------------------------------------
            $saveButtons = [
                'id'           => 'save_and_continue',
                'label'        => $this->__('Save And Continue Edit'),
                'class'        => 'add',
                'button_class' => '',
                'onclick'      => 'WalmartAccountObj.saveAndEditClick(\'\',\'walmartAccountEditTabs\')',
                'class_name'   => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
                'options'      => [
                    'save' => [
                        'label'   => $this->__('Save And Back'),
                        'onclick' => 'WalmartAccountObj.saveClick()',
                        'class'   => 'action-primary'
                    ]
                ]
            ];

            $this->addButton('save_buttons', $saveButtons);
            // ---------------------------------------
        }
    }

    protected function _prepareLayout()
    {
        $this->js->add(
            <<<JS
    require([
        'M2ePro/Walmart/Account',
    ], function() {
        window.WalmartAccountObj = new WalmartAccount();
    });
JS
        );

        return parent::_prepareLayout();
    }
}
