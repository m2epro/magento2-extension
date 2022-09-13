<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml;

class Support extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    /** @var \Ess\M2ePro\Helper\Module\Support */
    private $moduleSupportHelper;

    /**
     * @param \Ess\M2ePro\Helper\Module\Support $moduleSupportHelper
     * @param \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context
     * @param array $data
     */
    public function __construct(
        \Ess\M2ePro\Helper\Module\Support $moduleSupportHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        $this->moduleSupportHelper = $moduleSupportHelper;
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        $this->appendHelpBlock([
            'no_collapse' => true,
            'no_hide' => true,
            'content' => $this->__(
                <<<HTML
                <p>Have any questions regarding the use of M2E Pro, its functionality, technical aspects, or billing?
                You can always find answers in our
                <a href="%url_1%" target="_blank" class="external-link">documentation</a> or
                <a href="%url_2%" target="_blank" class="external-link">Knowledge Base</a>
                created specifically for M2E Pro clients. There is also a
                <a href="%url_3%" target="_blank" class="external-link">YouTube channel</a>
                with helpful video guides.</p>
                <p>In case you cannot find a solution to your problem within the available resources,
                feel free to reach out to M2E Pro Support Team by clicking Contact Us. If your subscription plan
                does not include a ticket system, you will receive an email with the plan's terms
                in response to your request.</p>
HTML
                ,
                $this->moduleSupportHelper->getDocumentationArticleUrl('x/O310B'),
                $this->moduleSupportHelper->getKnowledgebaseUrl(),
                $this->moduleSupportHelper->getYoutubeChannelUrl()
            )
        ]);

        parent::_prepareLayout();
    }

    public function toHtml()
    {
        $summaryInfo = \Ess\M2ePro\Helper\Json::encode(
            $this->moduleSupportHelper->getSummaryInfo()
        );

        $this->js->add(
            <<<JS
window.showContactUsWidget = function () {
    $('contact_us_button').hide();
    FreshworksWidget('open');
};

// Initialize FreshworksWidget
window.fwSettings = {
    widget_id: 9000000228
};
(function () {
    // code below used to save widget commands in queue if widget still not loaded
    // `q` means `queue`
    // widget will read this queue and run commands
    if (typeof window.FreshworksWidget != "function") {
        var handler = function () {
            handler.q.push(arguments)
        };
        handler.q = [];
        window.FreshworksWidget = handler;
    }
})();

FreshworksWidget('prefill', 'ticketForm', {
    custom_fields: {
        cf_summary_info: {$summaryInfo}
    }
});

FreshworksWidget('hide', 'ticketForm', ['custom_fields.cf_summary_info', 'custom_fields.cf_version']);
FreshworksWidget('hide');

JS
        );

        $this->js->addRequireJs(
            ['freshworks_widget' => '//widget.freshworks.com/widgets/9000000228.js'],
            ''
        );

        $button = <<<HTML
<div class="a-center">
    <input id="contact_us_button"
        value="Contact Us"
        class="action-primary m2epro-field-without-tooltip"
        type="button"
        onclick="showContactUsWidget()">
</div>
HTML;

        return parent::toHtml() . $button;
    }
}
