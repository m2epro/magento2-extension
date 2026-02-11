<?php

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Template\Repricer\Edit\Form;

class GeneralFieldset
{
    private \Ess\M2ePro\Model\Walmart\Account\Repository $walmartAccountRepository;

    public function __construct(
        \Ess\M2ePro\Model\Walmart\Account\Repository $walmartAccountRepository
    ) {
        $this->walmartAccountRepository = $walmartAccountRepository;
    }

    public function add(
        \Magento\Framework\Data\Form $form,
        array $formData
    ) {
        $fieldset = $this->createFieldset($form);
        $this->addTitleField($fieldset, $formData);
        $this->addAccountField($fieldset, $formData);
    }

    private function createFieldset(\Magento\Framework\Data\Form $form): \Magento\Framework\Data\Form\Element\Fieldset
    {
        return $form->addFieldset(
            'magento_block_walmart_template_repricer_general_fieldset',
            [
                'legend' => __('General'),
            ]
        );
    }

    private function addTitleField(\Magento\Framework\Data\Form\Element\Fieldset $fieldset, array $formData)
    {
        $fieldset->addField(
            'title',
            'text',
            [
                'name' => 'title',
                'label' => __('Title'),
                'value' => $formData['title'],
                'required' => true,
                'class' => '',
            ]
        );
    }

    private function addAccountField(\Magento\Framework\Data\Form\Element\Fieldset $fieldset, array $formData)
    {
        $fieldset->addField(
            'account',
            'select',
            [
                'name' => 'account_id',
                'label' => __('Account'),
                'values' => $this->getUsaAccountsOptions(),
                'value' => $formData['account_id'],
                'required' => true,
            ]
        );
    }

    private function getUsaAccountsOptions(): array
    {
        $accounts = $this->walmartAccountRepository->getAllItems();
        $options = [];
        foreach ($accounts as $account) {
            /** @var \Ess\M2ePro\Model\Walmart\Account $walmartAccount */
            $walmartAccount = $account->getChildObject();
            if ($walmartAccount->getMarketplaceId() === \Ess\M2ePro\Helper\Component\Walmart::MARKETPLACE_CA) {
                continue;
            }

            $options[] = [
                'label' => $account->getTitle(),
                'value' => $account->getId(),
            ];
        }

        return $options;
    }
}
