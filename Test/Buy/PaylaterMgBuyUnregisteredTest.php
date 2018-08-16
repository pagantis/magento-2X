<?php

namespace DigitalOrigin\Pmt\Test\Buy;

use DigitalOrigin\Pmt\Test\Common\AbstractMg21Selenium;

/**
 * Class PaylaterMgBuyUnregisteredTest
 *
 * @group magento-buy-unregistered
 */
class PaylaterMgBuyUnregisteredTest extends AbstractMg21Selenium
{
    /**
     * @throws \Exception
     */
    public function testBuy()
    {
        $this->goToProduct();
        $this->configureProduct();
        $this->checkProductPage();
        $this->goToCheckout();
        $this->prepareCheckout();
        $this->preparePaymentMethod();
        $this->verifyPaylater();
        $this->verifyOrder();
        $this->quit();
    }
}