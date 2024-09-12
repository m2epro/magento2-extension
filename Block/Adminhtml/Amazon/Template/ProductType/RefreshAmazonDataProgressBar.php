<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType;

class RefreshAmazonDataProgressBar extends \Magento\Backend\Block\Template
{
    public function _toHtml(): string
    {
        return '<div id="product_type_progress_bar"></div>';
    }
}
