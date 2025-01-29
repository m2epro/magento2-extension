<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Listing\Product;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractContainer;

class ShowOthersListingsProductsFilter extends AbstractContainer
{
    public const PARAM_NAME_SHOW_PRODUCT_IN_OTHER_LISTING = 'show_products_others_listings';
    public const PARAM_NAME_SHOW_CHILD_PRODUCTS_IN_VARIATIONS = 'show_child_products_in_variations';

    protected $_template = 'listing/product/show_products_others_listings_filter.phtml';

    public function getAllFilters(): array
    {
        return [
            [
                'param_name' => self::PARAM_NAME_SHOW_PRODUCT_IN_OTHER_LISTING,
                'label' => __('Show Products presented in other M2E Pro Listings'),
            ],
            [
                'param_name' => self::PARAM_NAME_SHOW_CHILD_PRODUCTS_IN_VARIATIONS,
                'label' => __('Show Child Products present in M2E Pro Listing as other Product Variations'),
            ],
        ];
    }

    public function renderFilter(array $filterData): string
    {
        $onclick = sprintf(
            "setLocation('%s');",
            $this->getFilterUrl($filterData['param_name'])
        );

        $inputHtml = sprintf(
            '<input id="%s" type="checkbox" class="admin__control-checkbox" onclick="%s" %s>',
            $filterData['param_name'],
            $onclick,
            $this->isChecked($filterData['param_name']) ? 'checked="checked"' : ''
        );

        $labelHtml = sprintf(
            '<label for="%s"><span>%s</span></label>',
            $filterData['param_name'],
            $filterData['label']
        );

        return "<div class='item'>$inputHtml $labelHtml</div>";
    }

    private function getFilterUrl(string $paramName): string
    {
        $params = $this->getRequest()->getParams();

        if ($this->isChecked($paramName)) {
            unset($params[$paramName]);
        } else {
            $params[$paramName] = true;
        }

        return $this->getUrl('*/' . $this->getController() . '/*', $params);
    }

    private function isChecked(string $paramName): bool
    {
        return !empty($this->getRequest()->getParam($paramName));
    }

    // ----------------------------------------

    private function getController()
    {
        return $this->getData('controller');
    }

    private function getComponentMode()
    {
        return $this->getData('component_mode');
    }
}
