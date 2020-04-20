<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Magento;

use Ess\M2ePro\Model\AbstractModel;

/**
 * Class \Ess\M2ePro\Model\Magento\Customer
 */
class Customer extends AbstractModel
{
    const FAKE_EMAIL_POSTFIX = '@dummy.email';

    protected $customerDataFactory;

    protected $addressDataFactory;

    protected $mathRandom;

    protected $customerFactory;

    protected $addressFactory;

    protected $resourceConnection;

    protected $customer;

    //########################################

    public function __construct(
        \Magento\Customer\Api\Data\CustomerInterfaceFactory $customerDataFactory,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        array $data = []
    ) {
        $this->customerDataFactory = $customerDataFactory;
        $this->addressDataFactory = $addressDataFactory;
        $this->mathRandom = $mathRandom;
        $this->customerFactory = $customerFactory;
        $this->addressFactory = $addressFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($helperFactory, $modelFactory, $data);
    }

    //########################################

    /**
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    //########################################

    public function buildCustomer()
    {
        $password = $this->mathRandom->getRandomString(7);

        /**
         * Magento can replace customer group to the default.
         * vendor/magento/module-customer/Observer/AfterAddressSaveObserver.php:121
         * Can be disabled here:
         * Customers -> Customer Configuration -> Create new account options -> Automatic Assignment to Customer Group
         */
        $customerData = $this->customerDataFactory->create()
            ->setFirstname($this->getData('customer_firstname'))
            ->setMiddlename($this->getData('customer_middlename'))
            ->setLastname($this->getData('customer_lastname'))
            ->setWebsiteId($this->getData('website_id'))
            ->setGroupId($this->getData('group_id'))
            ->setEmail($this->getData('email'))
            ->setConfirmation($password);

        $this->customer = $this->customerFactory->create();
        $this->customer->updateData($customerData);
        $this->customer->setPassword($password);
        $this->customer->save();

        // Add customer address
        // ---------------------------------------
        $addressModel = $this->addressFactory->create();
        $this->_updateAddress($addressModel);

        $addressData = $this->addressDataFactory->create()
            ->setIsDefaultBilling(true)
            ->setIsDefaultShipping(true);

        $addressModel->updateData($addressData);

        $addressModel->setCustomer($this->customer);
        $addressModel->save();

        $this->customer->addAddress($addressModel);
        // ---------------------------------------
    }

    public function updateAddress(\Magento\Customer\Model\Customer $customerObject)
    {
        $this->customer = $customerObject;

        foreach ($customerObject->getPrimaryAddresses() as $addressModel) {
            $this->_updateAddress($addressModel);
            $addressModel->save();
        }
    }

    //########################################

    private function _updateAddress(\Magento\Customer\Model\Address $addressModel)
    {
        $street = $this->getData('street');
        if (!is_array($street)) {
            $street = explode('; ', $street);
        }

        $addressData = $this->addressDataFactory->create()
            ->setFirstname($this->getData('firstname'))
            ->setMiddlename($this->getData('middlename'))
            ->setLastname($this->getData('lastname'))
            ->setCountryId($this->getData('country_id'))
            ->setCity($this->getData('city'))
            ->setPostcode($this->getData('postcode'))
            ->setTelephone($this->getData('telephone'))
            ->setStreet($street)
            ->setCompany($this->getData('company'));

        $addressModel->updateData($addressData);
        /**
         * Updating 'region_id' value to null will be skipped in
         * vendor/magento/framework/Reflection/DataObjectProcessor.php::buildOutputDataArray()
         *
         * So, we are forced to use separate setter for 'region_id' to bypass this validation
         */
        $addressModel->setRegionId($this->getData('region_id'));
    }

    //########################################

    public function buildAttribute($code, $label)
    {
        try {
            /** @var \Ess\M2ePro\Model\Magento\Attribute\Builder $attributeBuilder */
            $attributeBuilder = $this->modelFactory->getObject('Magento_Attribute_Builder');
            $attributeBuilder->setCode($code);
            $attributeBuilder->setLabel($label);
            $attributeBuilder->setInputType('text');
            $attributeBuilder->setEntityTypeId(
                $this->customerFactory->create()->getEntityType()->getId()
            );
            $attributeBuilder->setParams(['default_value' => '']);

            $result = $attributeBuilder->save();
            if (!$result['result']) {
                return;
            }

            /** @var \Magento\Eav\Model\Entity\Attribute $attribute */
            $attribute = $result['obj'];

            $defaultAttributeSetId = $this->getDefaultAttributeSetId();

            $this->addAttributeToGroup(
                $attribute->getId(),
                $defaultAttributeSetId,
                $this->getDefaultAttributeGroupId($defaultAttributeSetId)
            );
        } catch (\Exception $exception) {
            $this->helperFactory->getObject('Module\Exception')->process($exception, false);
        }
    }

    // ---------------------------------------

    private function addAttributeToGroup($attributeId, $attributeSetId, $attributeGroupId)
    {
        $connWrite = $this->resourceConnection->getConnection();

        $data = [
            'entity_type_id'      => $this->customerFactory->create()->getEntityType()->getId(),
            'attribute_set_id'    => $attributeSetId,
            'attribute_group_id'  => $attributeGroupId,
            'attribute_id'        => $attributeId,
        ];

        $connWrite->insert(
            $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('eav_entity_attribute'),
            $data
        );
    }

    private function getDefaultAttributeSetId()
    {
        $connRead = $this->resourceConnection->getConnection();

        $select = $connRead->select()
            ->from(
                $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('eav_entity_type'),
                'default_attribute_set_id'
            )
            ->where('entity_type_id = ?', $this->customerFactory->create()->getEntityType()->getId());

        return $connRead->fetchOne($select);
    }

    private function getDefaultAttributeGroupId($attributeSetId)
    {
        $connRead = $this->resourceConnection->getConnection();

        $select = $connRead->select()
            ->from(
                $this->getHelper('Module_Database_Structure')->getTableNameWithPrefix('eav_attribute_group'),
                'attribute_group_id'
            )
            ->where('attribute_set_id = ?', $attributeSetId)
            ->order(['default_id ' . \Magento\Framework\DB\Select::SQL_DESC, 'sort_order'])
            ->limit(1);

        return $connRead->fetchOne($select);
    }

    //########################################
}
