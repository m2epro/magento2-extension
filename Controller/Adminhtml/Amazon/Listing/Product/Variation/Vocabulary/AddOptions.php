<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Vocabulary;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class AddOptions extends Main
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
        $optionsData          = $this->getRequest()->getParam('options_data');
        $isRememberAutoAction = (bool)$this->getRequest()->getParam('is_remember', false);
        $needAddToVocabulary  = (bool)$this->getRequest()->getParam('need_add', false);

        if (!empty($optionsData)) {
            $optionsData = $this->helperData->jsonDecode($optionsData);
        }

        if (!$isRememberAutoAction && !$needAddToVocabulary) {
            return;
        }

        if ($isRememberAutoAction && !$needAddToVocabulary) {
            $this->vocabularyHelper->disableOptionAutoAction();
            return;
        }

        if (!$needAddToVocabulary) {
            return;
        }

        if ($isRememberAutoAction) {
            $this->vocabularyHelper->enableOptionAutoAction();
        }

        if (empty($optionsData)) {
            return;
        }

        foreach ($optionsData as $channelAttribute => $options) {
            foreach ($options as $productOption => $channelOption) {
                $this->vocabularyHelper->addOption($productOption, $channelOption, $channelAttribute);
            }
        }
    }
}
