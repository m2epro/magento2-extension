<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\SellingFormat;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;
use Ess\M2ePro\Helper\Component\Amazon;

class Save extends Template
{
    protected $dateTime;

    //########################################

    public function __construct(
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    )
    {
        $this->dateTime = $dateTime;
        parent::__construct($amazonFactory, $context);
    }

    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');
            return;
        }

        $id = $this->getRequest()->getParam('id');

        // Base prepare
        // ---------------------------------------
        $data = array();

        $keys = array(
            'title',

            'is_regular_customer_allowed',
            'is_business_customer_allowed',

            'qty_mode',
            'qty_custom_value',
            'qty_custom_attribute',
            'qty_percentage',
            'qty_modification_mode',
            'qty_min_posted_value',
            'qty_max_posted_value',

            'regular_price_mode',
            'regular_price_coefficient',
            'regular_price_custom_attribute',

            'regular_map_price_mode',
            'regular_map_price_custom_attribute',

            'regular_sale_price_mode',
            'regular_sale_price_coefficient',
            'regular_sale_price_custom_attribute',

            'regular_price_variation_mode',

            'regular_sale_price_start_date_mode',
            'regular_sale_price_end_date_mode',

            'regular_sale_price_start_date_value',
            'regular_sale_price_end_date_value',

            'regular_sale_price_start_date_custom_attribute',
            'regular_sale_price_end_date_custom_attribute',

            'regular_price_vat_percent',

            'business_price_mode',
            'business_price_coefficient',
            'business_price_custom_attribute',

            'business_price_variation_mode',

            'business_price_vat_percent',

            'business_discounts_mode',
            'business_discounts_tier_coefficient',
            'business_discounts_tier_customer_group_id',
        );

        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        if ($data['regular_sale_price_start_date_value'] === '') {
            $data['regular_sale_price_start_date_value'] = $this->getHelper('Data')->getCurrentGmtDate(
                false, 'Y-m-d 00:00:00'
            );
        } else {
            $data['regular_sale_price_start_date_value'] = $this->dateTime->formatDate(
                $data['regular_sale_price_start_date_value'],
                true
            );
        }
        if ($data['regular_sale_price_end_date_value'] === '') {
            $data['regular_sale_price_end_date_value'] = $this->getHelper('Data')->getCurrentGmtDate(
                false, 'Y-m-d 00:00:00'
            );
        } else {
            $data['regular_sale_price_end_date_value'] = $this->dateTime->formatDate(
                $data['regular_sale_price_end_date_value'],
                true
            );
        }

        if (empty($data['is_business_customer_allowed'])) {
            unset($data['business_price_mode']);
            unset($data['business_price_coefficient']);
            unset($data['business_price_custom_attribute']);
            unset($data['business_price_variation_mode']);
            unset($data['business_price_vat_percent']);
            unset($data['business_discounts_mode']);
            unset($data['business_discounts_tier_coefficient']);
            unset($data['business_discounts_tier_customer_group_id']);
        }

        $data['title'] = strip_tags($data['title']);
        // ---------------------------------------

        // Add or update model
        // ---------------------------------------
        $model = $this->amazonFactory->getObject('Template\SellingFormat');

        $oldData = [];

        if ($id) {
            $model->load($id);

            $oldData = array_merge(
                $model->getDataSnapshot(),
                $model->getChildObject()->getDataSnapshot()
            );
        }

        $model->addData($data)->save();
        $model->getChildObject()->addData(array_merge(
            [$model->getResource()->getChildPrimary(Amazon::NICK) => $model->getId()],
            $data
        ));
        $model->save();

        if ($this->getHelper('Component\Amazon\Business')->isEnabled()) {
            $this->saveDiscounts($model->getId(), $post);
        }

        $newData = array_merge($model->getDataSnapshot(), $model->getChildObject()->getDataSnapshot());
        $model->getChildObject()->setSynchStatusNeed($newData, $oldData);

        if ($this->isAjax()) {
            $this->setJsonContent([
                'status' => true
            ]);
            return $this->getResult();
        }

        $id = $model->getId();

        $this->messageManager->addSuccess($this->__('Policy was successfully saved'));
        return $this->_redirect($this->getHelper('Data')->getBackUrl('*/amazon_template/index', array(), array(
            'edit' => array(
                'id' => $id,
                'wizard' => $this->getRequest()->getParam('wizard'),
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ),
        )));
    }

    //########################################

    private function saveDiscounts($templateId, $post)
    {
        $amazonTemplateSellingFormatBusinessDiscountTable = $this->activeRecordFactory->getObject(
            'Amazon\Template\SellingFormat\BusinessDiscount'
        )->getResource()->getMainTable();

        $this->resourceConnection->getConnection()->delete(
            $amazonTemplateSellingFormatBusinessDiscountTable,
            array(
                'template_selling_format_id = ?' => (int)$templateId
            )
        );

        if (empty($post['is_business_customer_allowed']) ||
            empty($post['business_discount']) || empty($post['business_discount']['qty'])
        ) {
            return;
        }

        $discounts = array();
        foreach ($post['business_discount']['qty'] as $i => $qty) {

            if ((string)$i == '%i%') {
                continue;
            }

            $attribute = empty($post['business_discount']['attribute']) ?
                '' : $post['business_discount']['attribute'][$i];

            $mode = empty($post['business_discount']['mode'][$i]) ?
                '' : $post['business_discount']['mode'][$i];

            $coefficient = empty($post['business_discount']['coefficient'][$i]) ?
                '' : $post['business_discount']['coefficient'][$i];

            $discounts[] = array(
                'template_selling_format_id' => $templateId,
                'qty'                        => $qty,
                'mode'                       => $mode,
                'attribute'                  => $attribute,
                'coefficient'                => $coefficient
            );
        }

        if (empty($discounts)) {
            return;
        }

        usort($discounts, function($a, $b)
        {
            return $a["qty"] > $b["qty"];
        });

        $this->resourceConnection->getConnection()->insertMultiple(
            $amazonTemplateSellingFormatBusinessDiscountTable, $discounts
        );
    }

    //########################################
}