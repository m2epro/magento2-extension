<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\View;

class Ebay
{
    public const NICK  = 'ebay';

    public const WIZARD_INSTALLATION_NICK = 'installationEbay';
    public const MENU_ROOT_NODE_NICK = 'Ess_M2ePro::ebay';

    public const MODE_SIMPLE = 'simple';
    public const MODE_ADVANCED = 'advanced';

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory */
    private $ebayFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $activeRecordFactory;
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory */
    private $modelFactory;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translation;
    /** @var \Ess\M2ePro\Helper\Module\Wizard */
    private $wizard;
    /** @var \Ess\M2ePro\Helper\Data\Cache\Runtime */
    private $runtimeCache;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $modelFactory,
        \Ess\M2ePro\Helper\Module\Translation $translation,
        \Ess\M2ePro\Helper\Module\Wizard $wizard,
        \Ess\M2ePro\Helper\Data\Cache\Runtime $runtimeCache
    ) {
        $this->ebayFactory = $ebayFactory;
        $this->activeRecordFactory = $activeRecordFactory;
        $this->modelFactory = $modelFactory;
        $this->translation = $translation;
        $this->wizard = $wizard;
        $this->runtimeCache = $runtimeCache;
    }

    // ----------------------------------------

    /**
     * @return \Magento\Framework\Phrase|mixed|string
     */
    public function getTitle(): string
    {
        return $this->translation->__('eBay Integration');
    }

    /**
     * @return \Magento\Framework\Phrase|mixed|string
     */
    public function getMenuRootNodeLabel(): string
    {
        return $this->getTitle();
    }

    /**
     * @return string
     */
    public function getWizardInstallationNick(): string
    {
        return self::WIZARD_INSTALLATION_NICK;
    }

    /**
     * @return bool
     */
    public function isInstallationWizardFinished(): bool
    {
        return $this->wizard->isFinished(
            $this->getWizardInstallationNick()
        );
    }

    // ----------------------------------------

    /**
     * @param $accountId
     *
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isFeedbacksShouldBeShown($accountId = null): bool
    {
        $accountCollection = $this->modelFactory->getObject('Ebay\Account')->getCollection();
        $accountCollection->addFieldToFilter('feedbacks_receive', 1);

        $feedbackCollection = $this->activeRecordFactory->getObject('Ebay\Feedback')->getCollection();

        if ($accountId !== null) {
            $accountCollection->addFieldToFilter(
                'account_id',
                $accountId
            );
            $feedbackCollection->addFieldToFilter(
                'account_id',
                $accountId
            );
        }

        return $accountCollection->getSize() || $feedbackCollection->getSize();
    }

    //----------------------------------------

    /**
     * @param $listingId
     *
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isDuplicatesFilterShouldBeShown($listingId = null): bool
    {
        $sessionCache = $this->runtimeCache;

        if ($sessionCache->getValue('is_duplicates_filter_should_be_shown') !== null) {
            return $sessionCache->getValue('is_duplicates_filter_should_be_shown');
        }

        $collection = $this->ebayFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('is_duplicate', 1);
        $listingId && $collection->addFieldToFilter('listing_id', (int)$listingId);

        $result = (bool)$collection->getSize();
        $sessionCache->setValue('is_duplicates_filter_should_be_shown', $result);

        return $result;
    }
}
