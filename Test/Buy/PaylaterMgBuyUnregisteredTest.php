<?php

namespace DigitalOrigin\Pmt\Test\Buy;

use DigitalOrigin\Pmt\Test\Common\AbstractMg21Selenium;


/**
 * Class pagantisMgBuyUnregisteredTest
 *
 * @group magento-buy-unregistered
 */
class pagantisMgBuyUnregisteredTest extends AbstractMg21Selenium
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
        $this->verifypagantis();
        $this->verifyOrder();
        $this->quit();
    }
}