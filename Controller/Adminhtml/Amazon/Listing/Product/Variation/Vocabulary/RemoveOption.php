<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Vocabulary;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class RemoveOption extends Main
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Vocabulary */
    protected $vocabularyHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;

    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Component\Amazon\Vocabulary $vocabularyHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->vocabularyHelper = $vocabularyHelper;
        $this->helperData = $helperData;
    }

    public function execute()
    {
        $productOption = $this->getRequest()->getParam('product_option');
        $productOptionsGroup = $this->getRequest()->getParam('product_options_group');
        $channelAttr = $this->getRequest()->getParam('channel_attr');

        if (empty($productOption) || empty($productOptionsGroup) || empty($channelAttr)) {
            $this->setAjaxContent('You should provide correct parameters.', false);
            return $this->getResult();
        }

        if (!is_array($productOptionsGroup)) {
            $productOptionsGroup = htmlspecialchars_decode($productOptionsGroup);
            $productOptionsGroup = $this->helperData->jsonDecode($productOptionsGroup);
        }

        $this->vocabularyHelper->removeOptionFromLocalStorage($productOption, $productOptionsGroup, $channelAttr);

        $this->setJsonContent([
            'success' => true
        ]);

        return $this->getResult();
    }
}
