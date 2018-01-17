<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Paylater\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Paylater\Gateway\Http\Client\ClientMock;

/**
 * Class ResponseCodeValidator
 * @package Paylater\Gateway\Validator
 */
class ResponseCodeValidator extends AbstractValidator
{
    /**
     * Const ResultCode
     */
    const RESULT_CODE = 'RESULT_CODE';

    /**
     * Performs validation of result code
     *
     * @param array $validationSubject
     *
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        if (!isset($validationSubject['response']) || !is_array($validationSubject['response'])) {
            throw new \InvalidArgumentException('Response does not exist');
        }

        $response = $validationSubject['response'];

        if ($this->isSuccessfulTransaction($response)) {
            return $this->createResult(
                true,
                array()
            );
        } else {
            return $this->createResult(
                false,
                array(__('Gateway rejected the transaction.'))
            );
        }
    }

    /**
     * @param array $response
     *
     * @return bool
     */
    private function isSuccessfulTransaction(array $response)
    {
        return isset($response[self::RESULT_CODE])
        && $response[self::RESULT_CODE] !== ClientMock::FAILURE;
    }
}
