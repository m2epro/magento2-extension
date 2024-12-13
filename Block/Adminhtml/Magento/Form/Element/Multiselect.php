<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Magento\Form\Element;

class Multiselect extends \Magento\Framework\Data\Form\Element\Multiselect
{
    public function __construct(
        \Magento\Framework\Data\Form\Element\Factory $factoryElement,
        \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection,
        \Magento\Framework\Escaper $escaper,
        $data = [],
        ?\Magento\Framework\View\Helper\SecureHtmlRenderer $secureRenderer = null,
        ?\Magento\Framework\Math\Random $random = null
    ) {
        parent::__construct(
            $factoryElement,
            $factoryCollection,
            $escaper,
            $data,
            $secureRenderer,
            $random
        );

        $this->setSize($data['size'] ?? 10);
    }
}
