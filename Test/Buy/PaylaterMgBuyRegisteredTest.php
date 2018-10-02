<?php

namespace DigitalOrigin\Pmt\Test\Buy;

use DigitalOrigin\Pmt\Test\Common\AbstractMg21Selenium;
use Httpful\Request;

/**
 * @requires magento-install
 * @requires magento-register
 *
 * @group magento-buy-registered
 */
class PaylaterMgBuyRegisteredTest extends AbstractMg21Selenium
{
    /**
     * @var string
     */
    protected $checkoutPrice;

    /**
     * @var string
     */
    protected $confirmationPrice;

    /**
     * @var string
     */
    protected $notifyUrl;

    /**
     * @throws  \Exception
     */
    public function testBuy()
    {
        $this->createAccount();
        $this->goToProduct();
        $this->configureProduct();
        $this->checkProductPage();
        $this->goToCheckout();
        $this->goToPayment();
        $this->setCheckoutPrice($this->preparePaymentMethod());
        $this->verifyPaylater();
        $this->verifyOrder();
        $this->setConfirmationPrice($this->verifyOrderInformation());
        $this->comparePrices();
        $this->checkProcessed();
        $this->quit();
    }

    private function comparePrices()
    {
        $this->assertContains($this->getCheckoutPrice(), $this->getConfirmationPrice(), "PR46");
    }

    private function checkProcessed()
    {
        $orderUrl = $this->webDriver->getCurrentURL();
        $this->assertNotEmpty($orderUrl);

        $orderArray = explode('/', $orderUrl);
        $magentoOrderId = (int)$orderArray['8'] + 1;
        $this->assertNotEmpty($magentoOrderId);
        $notifyUrl = self::MAGENTO_URL.self::NOTIFICATION_FOLDER.'?'.self::NOTIFICATION_PARAMETER.'='.$magentoOrderId;
        $response = Request::post($notifyUrl)->expects('json')->send();
        $this->assertNotEmpty($response->body->result);
        $this->assertContains(self::ALREADY_TITLE, $response->body->result_description, "PR51=>".$response->body->result);

        $magentoOrderId = 0;
        $notifyUrl = self::MAGENTO_URL.self::NOTIFICATION_FOLDER.'?'.self::NOTIFICATION_PARAMETER.'='.$magentoOrderId;
        $response = Request::post($notifyUrl)->expects('json')->send();
        $this->assertNotEmpty($response->body->result);
        $this->assertContains(self::NOORDER_TITLE, $response->body->result, "PR53=>".$response->body->result);
    }

    /**
     * @return string
     */
    public function getCheckoutPrice()
    {
        return $this->checkoutPrice;
    }

    /**
     * @param string $checkoutPrice
     */
    public function setCheckoutPrice($checkoutPrice)
    {
        $this->checkoutPrice = $checkoutPrice;
    }

    /**
     * @return string
     */
    public function getConfirmationPrice()
    {
        return $this->confirmationPrice;
    }

    /**
     * @param string $confirmationPrice
     */
    public function setConfirmationPrice($confirmationPrice)
    {
        $this->confirmationPrice = $confirmationPrice;
    }

    /**
     * @return string
     */
    public function getNotifyUrl()
    {
        return $this->notifyUrl;
    }

    /**
     * @param string $notifyUrl
     */
    public function setNotifyUrl($notifyUrl)
    {
        $this->notifyUrl = $notifyUrl;
    }
}
