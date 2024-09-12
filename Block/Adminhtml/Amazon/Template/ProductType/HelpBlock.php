<?php

declare(strict_types=1);

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Template\ProductType;

class HelpBlock extends \Ess\M2ePro\Block\Adminhtml\HelpBlock
{
    private \Ess\M2ePro\Helper\Module\Support $supportHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $supportHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->supportHelper = $supportHelper;
        parent::__construct($context, $data);
    }

    public function getContent(): string
    {
        return (string)__(
            'The page displays Amazon Product Types that are currently used in your M2E Pro Listings.<br/><br/>

            Here you can add a new Product Type, edit or delete existing ones.
            Learn how to manage Amazon Product Types in
            <a href="%url" target="_blank" class="external-link">this article</a>.<br/><br/>
            To ensure that you have the most up-to-date Product Type information in your M2E Pro,
            simply click the <b>Refresh Amazon Data</b> button.
            This will synchronize any changes made to Product Types on Amazon. Whether certain specifics have been
            added or removed, you will see the updated information after the data is refreshed.',
            ['url' => $this->supportHelper->getDocumentationArticleUrl('amazon-product-type')]
        );
    }
}
