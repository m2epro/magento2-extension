<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Repricer;

class Edit extends \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Edit
{
    private \Ess\M2ePro\Model\Walmart\Template\Repricer $repricerTemplate;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Template\Repricer $repricerTemplate,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Widget $context,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        $this->repricerTemplate = $repricerTemplate;
        parent::__construct($context, $dataHelper, $globalDataHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('walmartTemplateRepricerEdit');
        // ---------------------------------------

        // Set buttons actions
        // ---------------------------------------
        $this->buttonList->remove('delete');
        $this->buttonList->remove('save');
        $this->buttonList->remove('reset');
        // ---------------------------------------

        $isSaveAndClose = (bool)$this->getRequest()->getParam('close_on_save', false);

        if (!$isSaveAndClose && $this->isEditMode()) {
            $headId = 'walmart-template-repricer';
            // ---------------------------------------
            $this->buttonList->add('duplicate', [
                'label' => __('Duplicate'),
                'onclick' => "WalmartTemplateRepricerObj.duplicateClick('{$headId}')",
                'class' => 'add M2ePro_duplicate_button primary',
            ]);
            // ---------------------------------------

            // ---------------------------------------
            $this->buttonList->add('delete', [
                'label' => __('Delete'),
                'onclick' => 'CommonObj.deleteClick()',
                'class' => 'delete M2ePro_delete_button primary',
            ]);
            // ---------------------------------------
        }

        // ---------------------------------------

        $saveAndEditButtonOnClick = sprintf(
            "WalmartTemplateRepricerObj.saveAndEditClick('', undefined, '%s', '%s')",
            $this->getSaveConfirmationText(),
            \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Grid::TEMPLATE_REPRICER
        );

        if ($isSaveAndClose) {
            $saveButtonOnClick = sprintf(
                "WalmartTemplateRepricerObj.saveAndCloseClick('%s', '%s')",
                $this->getSaveConfirmationText(),
                \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Grid::TEMPLATE_REPRICER
            );

            $saveButtons = [
                'id' => 'save_and_close',
                'label' => __('Save And Close'),
                'class' => 'add',
                'button_class' => '',
                'onclick' => $saveButtonOnClick,
                'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
                'options' => [
                    'save' => [
                        'label' => __('Save And Continue Edit'),
                        'onclick' => $saveAndEditButtonOnClick,
                    ],
                ],
            ];
            $this->removeButton('back');
        } else {
            $saveButtonOnClick = sprintf(
                "WalmartTemplateRepricerObj.saveClick('%s', '%s')",
                $this->getSaveConfirmationText(),
                \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Grid::TEMPLATE_REPRICER
            );

            $saveButtons = [
                'id' => 'save_and_continue',
                'label' => __('Save And Continue Edit'),
                'class' => 'add',
                'onclick' => $saveAndEditButtonOnClick,
                'class_name' => \Ess\M2ePro\Block\Adminhtml\Magento\Button\SplitButton::class,
                'options' => [
                    'save' => [
                        'label' => __('Save And Back'),
                        'onclick' => $saveButtonOnClick,
                    ],
                ],
            ];
        }

        // ---------------------------------------
        $this->addButton('save_buttons', $saveButtons);
        // ---------------------------------------
    }

    private function isEditMode(): bool
    {
        return !$this->repricerTemplate->isObjectNew();
    }

    protected function _prepareLayout()
    {
        $formBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\Walmart\Template\Repricer\Edit\Form::class,
            '',
            [
                'repricerTemplate' => $this->repricerTemplate,
            ]
        );

        $this->setChild('form', $formBlock);

        return parent::_prepareLayout();
    }

    protected function _toHtml()
    {
        $this->jsUrl->addUrls([
            'formSubmit' => $this->getUrl(
                '*/walmart_template_repricer/save',
                ['_current' => true]
            ),
            'formSubmitNew' => $this->getUrl('*/walmart_template_repricer/save'),
            'deleteAction' => $this->getUrl(
                '*/walmart_template_repricer/delete',
                ['_current' => true]
            )
        ]);

        $url = $this->getUrl('*/walmart_template_repricer/getStrategies');

        $js = <<<JS
require([
    'M2ePro/Walmart/Template/Repricer'
    ], function() {
        window.WalmartTemplateRepricerObj = new WalmartTemplateRepricer({
            "getStrategiesUrl": "$url",
        });
    }
);
JS;

        $this->js->add($js);

        return parent::_toHtml();
    }
}
