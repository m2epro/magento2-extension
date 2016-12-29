<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Translation\Connector\Product\Add;

class MultipleResponser extends \Ess\M2ePro\Model\Translation\Connector\Command\Pending\Responser
{
    protected $listingsProducts = array();

    protected $failedListingsProducts = array();
    protected $succeededListingsProducts = array();

    private $descriptionTemplatesIds = array();

    protected $activeRecordFactory;

    // ########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Model\Connector\Connection\Response $response,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        array $params = array()
    )
    {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($ebayFactory, $response, $helperFactory, $modelFactory, $params);

        foreach ($this->params['products'] as $listingProductId => $listingProductData) {
            try {
                $this->listingsProducts[] = $this->ebayFactory->getObjectLoaded(
                    'Listing\Product',(int)$listingProductId
                );
            } catch (\Exception $exception) {}
        }
    }

    // ########################################

    public function failDetected($messageText)
    {
        parent::failDetected($messageText);

        $alreadyLoggedListings = array();
        foreach ($this->listingsProducts as $listingProduct) {

            $listingProduct->getChildObject()->setData(
                'translation_status',\Ess\M2ePro\Model\Ebay\Listing\Product::TRANSLATION_STATUS_PENDING
            )->save();

            /** @var $listingProduct \Ess\M2ePro\Model\Listing\Product */
            if (isset($alreadyLoggedListings[$listingProduct->getListingId()])) {
                continue;
            }

            $this->addListingsProductsLogsMessage(
                $listingProduct,
                $messageText,
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH
            );

            $alreadyLoggedListings[$listingProduct->getListingId()] = true;
        }
    }

    // ########################################

    protected function addListingsProductsLogsMessage(\Ess\M2ePro\Model\Listing\Product $listingProduct,
                                                      $text, $type = \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE,
                                                      $priority = \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM)
    {
        $action =\Ess\M2ePro\Model\Listing\Log::ACTION_TRANSLATE_PRODUCT;

        if ($this->getStatusChanger() == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_UNKNOWN) {
            $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN;
        } else if ($this->getStatusChanger() == \Ess\M2ePro\Model\Listing\Product::STATUS_CHANGER_USER) {
            $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_USER;
        } else {
            $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION;
        }

        /** @var  $logModel \Ess\M2ePro\Model\Listing\Log */
        $logModel = $this->activeRecordFactory->getObject('Listing\Log');
        $logModel->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);

        $logModel->addProductMessage($listingProduct->getListingId() ,
                                     $listingProduct->getProductId() ,
                                     $listingProduct->getId() ,
                                     $initiator ,
                                     $this->getLogsActionId() ,
                                     $action , $text, $type , $priority);
    }

    // ########################################

    protected function validateResponse()
    {
        return true;
    }

    protected function processResponseData()
    {
        $failedListingsProductsIds = array();

        // Check global messages
        //----------------------
        $globalMessages = $this->getResponse()->getMessages()->getEntities();

        foreach ($this->listingsProducts as $listingProduct) {

            $hasError = false;
            foreach ($globalMessages as $message) {

                !$hasError && $hasError = $message->isError();

                $this->addListingsProductsLogsMessage(
                    $listingProduct, $message->getText(), $this->getType($message), $this->getPriority($message)
                );

                if (strpos($message->getText(), 'code:64') !== false) {

                    preg_match("/amount_due\:(.*?)\s*,\s*currency\:(.*?)\s*\)/i", $message->getText(), $matches);

                    $additionalData = $listingProduct->getAdditionalData();
                    $additionalData['translation_service']['payment'] = array(
                        'amount_due' => $matches[1],
                        'currency'   => $matches[2],
                    );

                    $listingProduct->setData(
                        'additional_data', $this->getHelper('Data')->jsonEncode($additionalData)
                    )->save();
                    $listingProduct->getChildObject()->setData(
                        'translation_status',
                        \Ess\M2ePro\Model\Ebay\Listing\Product::TRANSLATION_STATUS_PENDING_PAYMENT_REQUIRED
                    )->save();
                }
            }

            if ($hasError && !in_array($listingProduct->getId(),$failedListingsProductsIds)) {
                $this->failedListingsProducts[] = $listingProduct;
                $failedListingsProductsIds[] = $listingProduct->getId();
            }
        }

        //----------------------

        $responseData = $this->getResponse()->getResponseData();

        foreach ($this->listingsProducts as $listingProduct) {

            if (in_array($listingProduct->getId(),$failedListingsProductsIds)) {
                continue;
            }

            $this->succeededListingsProducts[] = $listingProduct;

            foreach ($responseData['products'] as $responseProduct) {
               if ($responseProduct['sku'] == $this->params['products'][$listingProduct->getId()]['sku']) {
                    $this->updateProduct($listingProduct, $responseProduct);
                    break;
                }
            }

            // M2ePro\TRANSLATIONS
            // 'Product has been successfully Translated.',
            $this->addListingsProductsLogsMessage($listingProduct, 'Product has been successfully Translated.',
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS,
                \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM);
        }
    }

    // ########################################

    protected function updateProduct(\Ess\M2ePro\Model\Listing\Product $listingProduct, array $response)
    {
        $productData = array();
        $descriptionTemplate = $listingProduct->getChildObject()->getDescriptionTemplate();
        $oldDescriptionTemplateId = $descriptionTemplate->getId();

        if (!isset($this->descriptionTemplatesIds[$oldDescriptionTemplateId]) && (
            trim($descriptionTemplate->getData('title_template'))       != '#ebay_translated_title#'    ||
            trim($descriptionTemplate->getData('subtitle_template'))    != '#ebay_translated_subtitle#' ||
            trim($descriptionTemplate->getData('description_template')) != '#ebay_translated_description#')) {

            $data = $descriptionTemplate->getDataSnapshot();
            unset($data['id'], $data['update_date'], $data['create_date']);

            $data['title']                = $data['title']
                .$this->getHelper('Module\Translation')->__(' (Changed because Translation Service applied.)');
            $data['title_mode']           =\Ess\M2ePro\Model\Ebay\Template\Description::TITLE_MODE_CUSTOM;
            $data['title_template']       = '#ebay_translated_title#';
            $data['subtitle_mode']        =\Ess\M2ePro\Model\Ebay\Template\Description::SUBTITLE_MODE_CUSTOM;
            $data['subtitle_template']    = '#ebay_translated_subtitle#';
            $data['description_mode']     =\Ess\M2ePro\Model\Ebay\Template\Description::DESCRIPTION_MODE_CUSTOM;
            $data['description_template'] = '#ebay_translated_description#';
            $data['is_custom_template']   = 1;

            $newDescriptionTemplate = $this->modelFactory->getObject('Ebay\Template\Manager')
                ->setTemplate(\Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION)
                ->getTemplateBuilder()
                ->build($data);
            $this->descriptionTemplatesIds[$oldDescriptionTemplateId] = $newDescriptionTemplate->getId();
        }

        if (isset($this->descriptionTemplatesIds[$oldDescriptionTemplateId])) {
            $productData['template_description_custom_id'] = $this->descriptionTemplatesIds[$oldDescriptionTemplateId];
            $productData['template_description_mode']      =\Ess\M2ePro\Model\Ebay\Template\Manager::MODE_CUSTOM;
        }

        $attributes = array(
            'ebay_translated_title'       => array('label' => 'Ebay Translated Title', 'type' => 'text'),
            'ebay_translated_subtitle'    => array('label' => 'Ebay Translated Subtitle', 'type' => 'text'),
            'ebay_translated_description' => array('label' => 'Ebay Translated Description', 'type' => 'textarea')
        );
        $this->checkAndCreateMagentoAttributes($listingProduct->getMagentoProduct(), $attributes);

        $listingProduct->getMagentoProduct()
                       ->setAttributeValue('ebay_translated_title',       $response['title'])
                       ->setAttributeValue('ebay_translated_subtitle',    $response['subtitle'])
                       ->setAttributeValue('ebay_translated_description', $response['description']);
        //------------------------------

        $categoryPath = !is_null($response['category']['primary_id'])
            ? $this->getHelper('Component\Ebay\Category\Ebay')->getPath((int)$response['category']['primary_id'],
                                                                            $this->params['marketplace_id'])
            : '';

        $response['category']['path'] = $categoryPath;

        if ($categoryPath) {
            $data = $this->activeRecordFactory->getObject('Ebay\Template\Category')->getDefaultSettings();
            $data['category_main_id']   = (int)$response['category']['primary_id'];
            $data['category_main_path'] = $categoryPath;
            $data['marketplace_id']     = $this->params['marketplace_id'];
            $data['specifics']          = $this->getSpecificsData($response['item_specifics']);

            $productData['template_category_id'] =
                $this->modelFactory->getObject('Ebay\Template\Category\Builder')->build($data)->getId();
        } else {
            $response['category']['primary_id'] = null;
        }

        $additionalData = $listingProduct->getAdditionalData();
        $additionalData['translation_service']['to'] = array_merge(
            $additionalData['translation_service']['to'], $response
        );
        $productData['additional_data'] = $this->getHelper('Data')->jsonEncode($additionalData);

        $listingProduct->addData($productData)->save();
        $listingProduct->getChildObject()->addData(array(
            'translation_status' => \Ess\M2ePro\Model\Ebay\Listing\Product::TRANSLATION_STATUS_TRANSLATED,
            'translated_date'    => $this->getHelper('Data')->getCurrentGmtDate()
        ))->save();
    }

    // ########################################

    protected function getType(\Ess\M2ePro\Model\Connector\Connection\Response\Message $message)
    {
        if ($message->isWarning()) {
            return \Ess\M2ePro\Model\Log\AbstractModel::TYPE_WARNING;
        }

        if ($message->isSuccess()) {
            return \Ess\M2ePro\Model\Log\AbstractModel::TYPE_SUCCESS;
        }

        if ($message->isNotice()) {
            return \Ess\M2ePro\Model\Log\AbstractModel::TYPE_NOTICE;
        }

        return \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR;
    }

    protected function getPriority(\Ess\M2ePro\Model\Connector\Connection\Response\Message $message)
    {
        if ($message->isWarning() || $message->isSuccess()) {
            return \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_MEDIUM;
        }

        if ($message->isNotice()) {
            return \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_LOW;
        }

        return \Ess\M2ePro\Model\Log\AbstractModel::PRIORITY_HIGH;
    }

    // ########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    protected function getAccount()
    {
        return $this->getObjectByParam('Account','account_id');
    }

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    protected function getMarketplace()
    {
        return $this->getObjectByParam('Marketplace','marketplace_id');
    }

    //---------------------------------------

    protected function getStatusChanger()
    {
        return (int)$this->params['status_changer'];
    }

    protected function getLogsActionId()
    {
        return (int)$this->params['logs_action_id'];
    }

    // ########################################

    private function checkAndCreateMagentoAttributes($magentoProduct, array $attributes)
    {
        $attributeHelper = $this->getHelper('Magento\Attribute');

        $attributeSetId  = $magentoProduct->getProduct()->getAttributeSetId();
        $attributesInSet = $attributeHelper->getByAttributeSet($attributeSetId);

        /** @var \Ess\M2ePro\Model\Magento\AttributeSet\Group $model */
        $model = $this->modelFactory->getObject('Magento\AttributeSet\Group');
        $model->setGroupName('Ebay')
              ->setAttributeSetId($attributeSetId)
              ->save();

        foreach ($attributes as $attributeCode => $attributeProp) {

            if (!$attributeHelper->getByCode($attributeCode)) {

                /** @var \Ess\M2ePro\Model\Magento\Attribute\Builder $model */
                $model = $this->modelFactory->getObject('Magento\Attribute\Builder');
                $model->setCode($attributeCode)
                      ->setLabel($attributeProp['label'])
                      ->setInputType($attributeProp['type'])
                      ->setScope($model::SCOPE_STORE);

                $model->save();
            }

            if (!$attributeHelper->isExistInAttributesArray($attributeCode, $attributesInSet)) {

                /** @var \Ess\M2ePro\Model\Magento\Attribute\Relation $model */
                $model = $this->modelFactory->getObject('Magento\Attribute\Relation');
                $model->setCode($attributeCode)
                      ->setAttributeSetId($attributeSetId)
                      ->setGroupName('Ebay');

                $model->save();
            }
        }

        return true;
    }

    //---------------------------------------

    private function getSpecificsData($responseSpecifics)
    {
        $data = array();
        foreach ($responseSpecifics as $responseSpecific) {
            $data[] = array(
                'mode'                  =>\Ess\M2ePro\Model\Ebay\Template\Category\Specific::MODE_CUSTOM_ITEM_SPECIFICS,
                'attribute_title'       => $responseSpecific['name'],
                'value_mode'            => \Ess\M2ePro\Model\Ebay\Template\Category\Specific::VALUE_MODE_CUSTOM_VALUE,
                'value_ebay_recommended'=> $this->getHelper('Data')->jsonEncode(array()),
                'value_custom_value'    => join(",", $responseSpecific['value']),
                'value_custom_attribute'=> ''
            );
        }

        return $data;
    }

    // ########################################
}