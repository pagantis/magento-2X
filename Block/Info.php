<?php

namespace DigitalOrigin\Pmt\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;
use DigitalOrigin\Pmt\Gateway\Response\FraudHandler;

/**
 * Class Info
 * @package DigitalOrigin\Pmt\Block
 */
class Info extends ConfigurableInfo
{
    /**
     * Returns label
     *
     * @param string $field
     * @return Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }

    /**
     * Returns value view
     *
     * @param string $field
     * @param string $value
     * @return string | Phrase
     */
    protected function getValueView($field, $value)
    {
        switch ($field) {
            case FraudHandler::FRAUD_MSG_LIST:
                return implode('; ', $value);
        }
        return parent::getValueView($field, $value);
    }
}
