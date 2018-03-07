<?php

namespace DigitalOrigin\Pmt\Model\Adminhtml\Source;

use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class PaymentAction
 * @package DigitalOrigin\Pmt\Model\Adminhtml\Source
 */
class PaymentAction implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => AbstractMethod::ACTION_AUTHORIZE,
                'label' => __('Authorize')
            ]
        ];
    }
}
