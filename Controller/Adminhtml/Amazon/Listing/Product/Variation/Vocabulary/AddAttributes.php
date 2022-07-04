<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Listing\Product\Variation\Vocabulary;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Main;

class AddAttributes extends Main
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;

    /** @var \Ess\M2ePro\Helper\Component\Amazon\Vocabulary */
    protected $vocabularyHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Component\Amazon\Vocabulary $vocabularyHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->helperData = $helperData;
        $this->vocabularyHelper = $vocabularyHelper;
    }

    public function execute()
    {
        $attributes           = $this->getRequest()->getParam('attributes');
        $isRememberAutoAction = (bool)$this->getRequest()->getParam('is_remember', false);
        $needAddToVocabulary  = (bool)$this->getRequest()->getParam('need_add', false);

        if (!empty($attributes)) {
            $attributes = $this->helperData->jsonDecode($attributes);
        }

        if (!$isRememberAutoAction && !$needAddToVocabulary) {
            return;
        }

        if ($isRememberAutoAction && !$needAddToVocabulary) {
            $this->vocabularyHelper->disableAttributeAutoAction();
            return;
        }

        if (!$needAddToVocabulary) {
            return;
        }

        if ($isRememberAutoAction) {
            $this->vocabularyHelper->enableAttributeAutoAction();
        }

        if (empty($attributes)) {
            return;
        }

        foreach ($attributes as $productAttribute => $channelAttribute) {
            $this->vocabularyHelper->addAttribute($productAttribute, $channelAttribute);
        }
    }
}
