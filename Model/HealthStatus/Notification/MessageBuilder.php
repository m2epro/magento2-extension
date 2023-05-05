<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\HealthStatus\Notification;

class MessageBuilder extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Magento\Backend\Model\UrlInterface */
    private $urlBuilder;

    /**
     * @param \Magento\Backend\Model\UrlInterface $urlBuilder
     * @param \Ess\M2ePro\Helper\Factory $helperFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $data);

        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @return string
     */
    public function build(): string
    {
        return $this->getHeader() . ': ' . $this->getMessage();
    }

    /**
     * @return string
     */
    public function getHeader(): string
    {
        return (string)__('M2E Pro Health Status Notification');
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        $manageUrl = $this->urlBuilder->getUrl('m2epro/healthStatus/index');

        return (string)__(
            <<<HTML
Something went wrong with your M2E Pro running and some actions from your side are required.
You can find detailed information in <a target="_blank" href="{$manageUrl}">M2E Pro Health Status Center</a>.
HTML
        );
    }
}
