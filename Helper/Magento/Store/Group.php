<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Magento\Store;

class Group
{
    /** @var \Magento\Store\Api\Data\GroupInterface */
    private $defaultStoreGroup;
    /** @var \Magento\Catalog\Model\CategoryFactory */
    private $catalogCategoryFactory;
    /** @var \Magento\Store\Model\GroupFactory */
    private $storeGroupFactory;
    /** @var \Magento\Store\Model\StoreManagerInterface  */
    private $storeManager;
    /** @var \Ess\M2ePro\Helper\Magento\Store\Website */
    private $websiteHelper;
    /** @var \Ess\M2ePro\Helper\Module\Translation */
    private $translationHelper;

    /**
     * @param \Magento\Catalog\Model\CategoryFactory $catalogCategoryFactory
     * @param \Magento\Store\Model\GroupFactory $storeGroupFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Ess\M2ePro\Helper\Magento\Store\Website $websiteHelper
     * @param \Ess\M2ePro\Helper\Module\Translation $translationHelper
     */
    public function __construct(
        \Magento\Catalog\Model\CategoryFactory $catalogCategoryFactory,
        \Magento\Store\Model\GroupFactory $storeGroupFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Helper\Magento\Store\Website $websiteHelper,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper
    ) {
        $this->catalogCategoryFactory = $catalogCategoryFactory;
        $this->storeGroupFactory = $storeGroupFactory;
        $this->storeManager = $storeManager;
        $this->websiteHelper = $websiteHelper;
        $this->translationHelper = $translationHelper;
    }

    // ----------------------------------------

    /**
     * @param $entity
     *
     * @return bool
     */
    public function isExists($entity)
    {
        if ($entity instanceof \Magento\Store\Model\Group) {
            return (bool)$entity->getCode();
        }

        try {
            $this->storeManager->getGroup($entity);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function isChildOfWebsite($groupId, $websiteId)
    {
        $group = $this->storeManager->getGroup($groupId);
        return ($group->getWebsiteId() == $websiteId);
    }

    // ----------------------------------------

    /**
     * @return \Magento\Store\Api\Data\GroupInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDefault(): \Magento\Store\Api\Data\GroupInterface
    {
        if ($this->defaultStoreGroup === null) {
            $defaultWebsite = $this->storeManager->getWebsite(true);
            $defaultStoreGroupId = $defaultWebsite->getDefaultGroupId();

            $this->defaultStoreGroup = $this->storeManager->getGroup($defaultStoreGroupId);
        }

        return $this->defaultStoreGroup;
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDefaultGroupId(): int
    {
        return (int)$this->getDefault()->getId();
    }

    public function addGroup($websiteId, $name, $rootCategoryId)
    {
        if (!$this->websiteHelper->isExists($websiteId)) {
            $error = $this->translationHelper->__(
                'Website with id %value% does not exist.',
                (int)$websiteId
            );
            throw new \Ess\M2ePro\Model\Exception($error);
        }

        $group = $this->storeGroupFactory->create();
        $group->setId(null);
        $group->setName($name);

        $group->setWebsiteId($websiteId);
        $group->setWebsite($this->storeManager->getWebsite($websiteId));

        if (isset($rootCategoryId)) {
            $category = $this->catalogCategoryFactory->create()->load($rootCategoryId);

            if (!$category->hasEntityId()) {
                $error = $this->translationHelper->__(
                    'Category with %category_id% doen\'t exist',
                    $rootCategoryId
                );
                throw new \Ess\M2ePro\Model\Exception($error);
            }

            if ((int)$category->getLevel() !== 1) {
                $error = $this->translationHelper->__('Category of level 1 must be provided.');
                throw new \Ess\M2ePro\Model\Exception($error);
            }

            $group->setRootCategoryId($rootCategoryId);
        }

        $group->save();

        return $group;
    }

    public function getGroups($withDefault = false)
    {
        return $this->storeManager->getGroups($withDefault);
    }
}
