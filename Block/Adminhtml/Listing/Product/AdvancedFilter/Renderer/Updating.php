<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Listing\Product\AdvancedFilter\Renderer;

class Updating extends \Ess\M2ePro\Block\Adminhtml\Listing\Product\AdvancedFilter\AbstractRenderer
{
    /** @var int */
    private $updatedEntityId;
    /** @var string */
    private $viewStateKey;
    /** @var \Ess\M2ePro\Model\Magento\Product\Rule */
    private $ruleModel;
    /** @var \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Repository */
    private $repository;

    public function __construct(
        int $updatedEntityId,
        string $viewStateKey,
        \Ess\M2ePro\Model\Magento\Product\Rule $ruleModel,
        \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Repository $repository,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);

        $this->updatedEntityId = $updatedEntityId;
        $this->viewStateKey = $viewStateKey;
        $this->ruleModel = $ruleModel;
        $this->repository = $repository;
    }

    public function renderJs(
        \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsRenderer $js,
        \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsUrlRenderer $jsUrl,
        \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsTranslatorRenderer $jsTranslator
    ): void {
        $jsTranslator->addTranslations([
            'Update Filter' => __('Update Filter'),
            'Update' => __('Update'),
            'Cancel' => __('Cancel'),
        ]);

        $jsUrl->add(
            $this->getUrl('*/listing_product_advancedFilter/update'),
            'listing_product_advanced_filter/update'
        );
        $jsUrl->add(
            $this->getUrl('*/listing_product_advancedFilter/delete'),
            'listing_product_advanced_filter/delete'
        );

        $js->addRequireJs(
            ['af' => 'M2ePro/Listing/Product/AdvancedFilter/Updating'],
            <<<JS
            window.ListingProductAdvancedFilterUpdatingObj = new ListingProductAdvancedFilterUpdating();
            ListingProductAdvancedFilterUpdatingObj.init(
                '{$this->updatedEntityId}',
                '{$this->viewStateKey}',
                '{$this->ruleModel->getPrefix()}',
            );
JS
        );
    }

    public function renderHtml(string $searchBtnHtml, string $resetBtnHtml): string
    {
        $ruleBlock = $this->getLayout()
                          ->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Product\Rule::class)
                          ->setData(['rule_model' => $this->ruleModel]);

        /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Button $btn */
        $updateFilterBtn = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class);
        $updateFilterBtn->setData([
            'label' => __('Update Filter'),
            'class' => 'action-default scalable action-primary',
            'onclick' => 'ListingProductAdvancedFilterUpdatingObj.openUpdateFilterPopup()',
        ]);

        /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Button $backBtn */
        $backBtn = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class);
        $backBtn->setData([
            'label' => __('Back'),
            'class' => 'action-default scalable action-primary',
            'onclick' => 'ListingProductAdvancedFilterUpdatingObj.back()',
        ]);

        /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Button $deleteBtn */
        $deleteBtn = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class);
        $deleteBtn->setData([
            'label' => __('Delete'),
            'class' => 'action-default scalable action-tertiary',
            'onclick' => 'ListingProductAdvancedFilterUpdatingObj.delete()',
        ]);

        $buttons = $this->wrapFilterHtmlBtn(
            $backBtn->toHtml()
            . $resetBtnHtml
            . $updateFilterBtn->toHtml()
            . $deleteBtn->toHtml()
        );

        return $ruleBlock->toHtml() . $buttons . $this->getModalHtml();
    }

    private function getModalHtml(): string
    {
        $entity = $this->repository->getAdvancedFilter($this->updatedEntityId);

        $form = $this->_formFactory->create();
        $nameInput = $form->addField(
            'advanced_filter_name_input_update',
            'text',
            [
                'name' => 'filter_name',
                'label' => __('Filter Name'),
                'value' => $entity->getTitle(),
            ]
        );

        return '<div id="update_filter_popup_content" class="hidden">'
            . $nameInput->toHtml()
            . '</div>';
    }
}
