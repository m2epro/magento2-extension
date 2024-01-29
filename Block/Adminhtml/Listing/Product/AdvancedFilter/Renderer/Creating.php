<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Listing\Product\AdvancedFilter\Renderer;

class Creating extends \Ess\M2ePro\Block\Adminhtml\Listing\Product\AdvancedFilter\AbstractRenderer
{
    /** @var string */
    private $viewStateKey;
    /** @var \Ess\M2ePro\Model\Magento\Product\Rule */
    private $ruleModel;
    /** @var \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Repository */
    private $repository;

    public function __construct(
        string $viewStateKey,
        \Ess\M2ePro\Model\Magento\Product\Rule $ruleModel,
        \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Repository $repository,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);

        $this->ruleModel = $ruleModel;
        $this->viewStateKey = $viewStateKey;
        $this->repository = $repository;
    }

    public function renderJs(
        \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsRenderer $js,
        \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsUrlRenderer $jsUrl,
        \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsTranslatorRenderer $jsTranslator
    ): void {
        $jsTranslator->addTranslations([
            'Save Filter' => __('Save Filter'),
            'Save' => __('Save'),
            'Cancel' => __('Cancel'),
        ]);

        $jsUrl->add(
            $this->getUrl('*/listing_product_advancedFilter/save'),
            'listing_product_advanced_filter/save'
        );

        $js->addRequireJs(
            ['creating' => 'M2ePro/Listing/Product/AdvancedFilter/Creating'],
            <<<JS
            window.ListingProductAdvancedFilterCreatingObj = new ListingProductAdvancedFilterCreating();
            ListingProductAdvancedFilterCreatingObj.init(
                '{$this->ruleModel->getNick()}',
                '{$this->ruleModel->getPrefix()}',
                '{$this->viewStateKey}'
            )
JS
        );
    }

    public function renderHtml(string $searchBtnHtml, string $resetBtnHtml): string
    {
        $ruleBlock = $this->getLayout()
                          ->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule::class)
                          ->setData(['rule_model' => $this->ruleModel]);

        /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Button $btn */
        $createFilterBtn = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class);
        $createFilterBtn->setData([
            'label' => __('Save Filter'),
            'class' => 'action-default scalable action-primary',
            'onclick' => 'ListingProductAdvancedFilterCreatingObj.openSaveFilterPopup()',
        ]);

        $backBtnHtml = '';
        if ($this->repository->isExistItemsWithModelNick($this->ruleModel->getNick())) {
            /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Button $backBtn */
            $backBtn = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class);
            $backBtn->setData([
                'label' => __('Back'),
                'class' => 'action-default scalable action-primary',
                'onclick' => 'ListingProductAdvancedFilterCreatingObj.back()',
            ]);
            $backBtnHtml = $backBtn->toHtml();
        }

        $buttons = $this->wrapFilterHtmlBtn(
            $backBtnHtml
            . $searchBtnHtml
            . $resetBtnHtml
            . $createFilterBtn->toHtml()
        );

        return $ruleBlock->toHtml() . $buttons . $this->getModalHtml();
    }

    private function getModalHtml(): string
    {
        $form = $this->_formFactory->create();
        $nameInput = $form->addField(
            'advanced_filter_name_input_create',
            'text',
            [
                'name' => 'filter_name',
                'label' => __('Filter Name'),
            ]
        );

        return '<div id="new_filter_popup_content" class="hidden">'
            . $nameInput->toHtml()
            . '</div>';
    }
}
