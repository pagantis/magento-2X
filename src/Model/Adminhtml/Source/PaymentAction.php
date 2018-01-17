<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paylater\Model\Adminhtml\Source;

use Magento\Payment\Model\Method\AbstractMethod;

/**
 * Class PaymentAction
 * @package Paylater\Model\Adminhtml\Source
 */
class PaymentAction implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => AbstractMethod::ACTION_AUTHORIZE,
                'label' => __('Authorize')
            )
        );
    }
}
