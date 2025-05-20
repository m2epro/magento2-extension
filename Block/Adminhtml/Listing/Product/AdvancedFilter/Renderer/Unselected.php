<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Listing\Product\AdvancedFilter\Renderer;

class Unselected extends \Ess\M2ePro\Block\Adminhtml\Listing\Product\AdvancedFilter\AbstractRenderer
{
    use PrepareSelectTrait;

    /** @var string */
    private $ruleModelNick;
    /** @var \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Repository */
    private $repository;

    public function __construct(
        string $ruleModelNick,
        \Ess\M2ePro\Model\Listing\Product\AdvancedFilter\Repository $repository,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $data);

        $this->repository = $repository;
        $this->ruleModelNick = $ruleModelNick;
    }

    public function renderJs(
        \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsRenderer $js,
        \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsUrlRenderer $jsUrl,
        \Ess\M2ePro\Block\Adminhtml\Magento\Renderer\JsTranslatorRenderer $jsTranslator
    ): void {
        $js->addRequireJs(
            ['select' => 'M2ePro/Listing/Product/AdvancedFilter/Select'],
            <<<JS
            window.ListingProductAdvancedFilterSelectObj = new ListingProductAdvancedFilterSelect();
            ListingProductAdvancedFilterSelectObj.init();
            ListingProductAdvancedFilterSelectObj.initEvents();
JS
        );
    }

    public function renderHtml(string $searchBtnHtml, string $resetBtnHtml): string
    {
        $buttons = $this->wrapFilterHtmlBtn($searchBtnHtml . $resetBtnHtml);

        return $this->getFilterSelectHtml() . $buttons;
    }

    private function getFilterSelectHtml(): string
    {
        $entities = $this->repository->findItemsByModelNick($this->ruleModelNick);

        $values = $this->createSelect($entities);

        $addNew = __('Add New');

        $element = $this->_formFactory->create()->addField(
            'advanced_filter_list',
            self::SELECT,
            [
                'name' => 'rule_entity_id',
                'label' => __('Saved Filter'),
                'class' => 'advanced-filter-select',
                'values' => $values,
                'value' => null,
                'after_element_html' => <<<HTML
&nbsp;
<span style="line-height: 30px;">
    <a href="javascript: void(0);" onclick="ListingProductAdvancedFilterSelectObj.createNewFilter()">{$addNew}</a>
</span>
HTML
                ,
            ]
        );

        return sprintf('<div class="advanced-filter-select-container">%s</div>', $element->toHtml());
    }
}
