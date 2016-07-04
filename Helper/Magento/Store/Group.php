<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Magento\Store;

class Group extends \Ess\M2ePro\Helper\AbstractHelper
{
    private $defaultStoreGroup = NULL;

    protected $catalogCategoryFactory;
    protected $storeGroupFactory;
    protected $storeManager;

    //########################################

    public function __construct(
        \Magento\Catalog\Model\CategoryFactory $catalogCategoryFactory,
        \Magento\Store\Model\GroupFactory $storeGroupFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $this->catalogCategoryFactory = $catalogCategoryFactory;
        $this->storeGroupFactory = $storeGroupFactory;
        $this->storeManager = $storeManager;
        parent::__construct($helperFactory, $context);
    }

    //########################################

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

    //########################################

    public function getDefault()
    {
        if (is_null($this->defaultStoreGroup)) {

            $defaultWebsite = $this->storeManager->getWebsite(true);
            $defaultStoreGroupId = $defaultWebsite->getDefaultGroupId();

            $this->defaultStoreGroup = $this->storeManager->getGroup($defaultStoreGroupId);
        }

        return $this->defaultStoreGroup;
    }

    public function getDefaultGroupId()
    {
        return (int)$this->getDefault()->getId();
    }

    //########################################

    public function addGroup($websiteId, $name, $rootCategoryId)
    {
        if (!$this->getHelper('Magento\Store\Website')->isExists($websiteId)) {
            $error = $this->getHelper('Module\Translation')->__(
                'Website with id %value% does not exist.', (int)$websiteId
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
                $error = $this->getHelper('Module\Translation')->__(
                    'Category with %category_id% doen\'t exist', $rootCategoryId
                );
                throw new \Ess\M2ePro\Model\Exception($error);
            }

            if ((int)$category->getLevel() !== 1) {
                $error = $this->getHelper('Module\Translation')->__('Category of level 1 must be provided.');
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

    //########################################
}