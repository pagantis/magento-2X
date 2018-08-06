<?php

namespace DigitalOrigin\Test\Common;

use DigitalOrigin\Test\PaylaterMagentoTest;
use Facebook\WebDriver\Remote\LocalFileDetector;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;

/**
 * Class AbstractMg21Selenium
 * @package DigitalOrigin\Test\Common
 */
abstract class AbstractMg21Selenium extends PaylaterMagentoTest
{
    /**
     * @throws \Exception
     */
    public function loginToBackOffice()
    {
        $this->webDriver->get(self::MAGENTO_URL.self::BACKOFFICE_FOLDER);
        $emailElementSearch = WebDriverBy::id('username');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($emailElementSearch);
        $this->webDriver->wait()->until($condition);
        $this->findById('username')->clear()->sendKeys($this->configuration['backofficeUsername']);
        $this->findById('login')->clear()->sendKeys($this->configuration['backofficePassword']);
        $this->findById('login-form')->submit();
        $emailElementSearch = WebDriverBy::className('page-wrapper');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($emailElementSearch);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
    }

    /**
     * @require loginToBackOffice
     *
     * @throws \Exception
     */
    public function getPaylaterBackOffice()
    {
        $this->webDriver->get(self::MAGENTO_URL.self::BACKOFFICE_FOLDER);
        $this->findByLinkText('STORES')->click();

        $elementSearch = WebDriverBy::linkText('Configuration');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($elementSearch);
        $this->webDriver->wait()->until($condition);
        $this->findByLinkText('Configuration')->click();

        //Confirmamos que aparece el menu de tabs
        $elementSearch = WebDriverBy::id('system_config_tabs');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($elementSearch);
        $this->webDriver->wait()->until($condition);

        //Buscamos la palabra SALES dentro del div anterior
        $menuSearch = WebDriverBy::cssSelector("#system_config_tabs > div > div > strong");
        $menuElements = $this->webDriver->findElements($menuSearch);
        foreach ($menuElements as $menuElement) {
            if (strpos($menuElement->getText(), 'SALES', 0) !== false ||
                strpos($menuElement->getText(), 'sales', 0) !== false) {
                $menuElement->click();
                $this->assertContains('SALES', $menuElement->getText(), $menuElement->getText());
                break;
            }
        }

        $elementSearch = WebDriverBy::linkText('Payment Methods');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($elementSearch);
        $this->webDriver->wait()->until($condition);
        $this->findByLinkText('Payment Methods')->click();

        $elementSearch = WebDriverBy::id('payment_us_other_payment_methods-head');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($elementSearch);
        $this->webDriver->wait()->until($condition);
        $otherElement = $this->findById('payment_us_other_payment_methods-head');
        if ($otherElement->getAttribute('class')!='open') {
            $otherElement->click();
        }

        $verify = WebDriverBy::id('payment_us_paylater-head');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, "PR4");
        $paylaterElement = $this->findById('payment_us_paylater-head');
        if ($paylaterElement->getAttribute('class')!='open') {
            $paylaterElement->click();
        }

        $verify = WebDriverBy::id('payment_us_paylater_active');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
    }

    /**
     * @throws \Exception
     */
    public function loginToFrontend()
    {
        $this->webDriver->get(self::MAGENTO_URL);
        $loginButton = WebDriverBy::partialLinkText('Sign In');
        $condition = WebDriverExpectedCondition::elementToBeClickable($loginButton);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $this->webDriver->findElement($loginButton)->click();

        $verifyElement = WebDriverBy::id('login-form');
        $condition = WebDriverExpectedCondition::elementToBeClickable($verifyElement);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);

        $this->findById('email')->sendKeys($this->configuration['email']);
        $this->findById('pass')->sendKeys($this->configuration['password']);
        $this->findById('login-form')->submit();
    }

    /**
     *
     * @throws \Exception
     */
    public function logoutFromFrontend()
    {
        $this->webDriver->get(self::MAGENTO_URL.self::LOGOUT_FOLDER);

        $validatorSearch = WebDriverBy::className('base');
        $actualString = $this->webDriver->findElement($validatorSearch)->getText();
        $compareString = (strpos($actualString, "You are signed out")) === false ? false : true;
        $this->assertTrue($compareString, "PR1-PR4");
    }

    /**
     * @throws \Exception
     */
    public function createAccount()
    {
        $this->webDriver->get(self::MAGENTO_URL);
        $loginButtonSearch = WebDriverBy::linkText('Create an Account');
        $condition = WebDriverExpectedCondition::elementToBeClickable($loginButtonSearch);
        $this->webDriver->wait()->until($condition);
        $this->findByLinkText('Create an Account')->click();

        $random = rand(0, 1000);
        $this->findById('firstname')->clear()->sendKeys($this->configuration['firstname']);
        $this->findById('lastname')->sendKeys($this->configuration['lastname']);
        $this->findById('email_address')->sendKeys($random.$this->configuration['email']);
        $this->findById('password')->sendKeys($this->configuration['password']);
        $this->findById('password-confirmation')->sendKeys($this->configuration['password']);
        $this->findById('form-validate')->submit();

        $addressButton = WebDriverBy::partialLinkText('Address Book');
        $condition = WebDriverExpectedCondition::elementToBeClickable($addressButton);
        $this->webDriver->wait()->until($condition);

        $this->assertContains(
            $this->configuration['firstname'],
            $this->findByClass('block-dashboard-info')->getText()
        );
        $this->findByPartialLinkText('Address Book')->click();

        /*$addressLink = WebDriverBy::linkText('Edit Address');
        $condition = WebDriverExpectedCondition::elementToBeClickable($addressLink);
        $this->webDriver->wait()->until($condition);
        $this->findByLinkText('Edit Address')->click();*/

        $addressLink = WebDriverBy::id('firstname');
        $condition = WebDriverExpectedCondition::presenceOfElementLocated($addressLink);
        $this->webDriver->wait()->until($condition);

        $this->findById('telephone')->clear()->sendKeys($this->configuration['phone']);
        $this->findById('street_1')->sendKeys($this->configuration['street']);
        $this->findById('city')->sendKeys($this->configuration['city']);
        $this->findById('zip')->sendKeys($this->configuration['zip']);

        $this->webDriver->findElement(WebDriverBy::id('country'))
                        ->findElement(WebDriverBy::cssSelector("option[value='ES']"))
                        ->click();
        sleep(1);
        $this->webDriver->findElement(WebDriverBy::id('region_id'))
                        ->findElement(WebDriverBy::cssSelector("option[value='161']"))
                        ->click();
        $this->findById('form-validate')->submit();
    }

    /**
     * @param bool $addressExists
     * @param bool $verifySimulator
     *
     * @throws \Exception
     */
    public function goToCheckout($addressExists = false, $verifySimulator = true)
    {
        $shoppingCartSearch = WebDriverBy::id('shopping_cart');
        $this->webDriver->findElement($shoppingCartSearch)->click();
        $shoppingCartTitle = WebDriverBy::id('cart_title');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($shoppingCartTitle);
        $this->assertTrue((bool) $condition);
        $cartNavigation = WebDriverBy::className('cart_navigation');
        $nextButton = $cartNavigation->partialLinkText('Next');
        $this->webDriver->findElement($nextButton)->click();
        try {
            if ($addressExists) {
                throw new \Exception('Address exists');
            }
            $addressInputSearch = WebDriverBy::id('firstname');
            $condition = WebDriverExpectedCondition::visibilityOfElementLocated($addressInputSearch);
            $this->webDriver->wait()->until($condition);
            $this->assertTrue((bool) $condition);
            $this->findById('company')->clear()->sendKeys($this->configuration['company']);
            $this->findById('address1')->clear()->sendKeys('av.diagonal 579');
            $this->findById('postcode')->clear()->sendKeys($this->configuration['zip']);
            $this->findById('city')->clear()->sendKeys($this->configuration['city']);
            $this->findById('phone')->clear()->sendKeys($this->configuration['phone']);
            $this->findById('phone_mobile')->clear()->sendKeys($this->configuration['phone']);
            $this->findById('dni')->clear()->sendKeys($this->configuration['dni']);
            $this->moveToElementAndClick($this->findById('submitAddress'));
            $processAddress = WebDriverBy::name('processAddress');
            $condition = WebDriverExpectedCondition::visibilityOfElementLocated($processAddress);
            $this->webDriver->wait()->until($condition);
            $this->assertTrue((bool) $condition);
        } catch (\Exception $exception) {
            $processAddress = WebDriverBy::name('processAddress');
            $condition = WebDriverExpectedCondition::visibilityOfElementLocated($processAddress);
            $this->webDriver->wait()->until($condition);
            $this->assertTrue((bool) $condition);
        }
        $this->webDriver->findElement($processAddress)->click();
        $processCarrier = WebDriverBy::name('processCarrier');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($processCarrier);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $this->findById('cgv')->click();
        $this->webDriver->findElement($processCarrier)->click();
        $hookPayment = WebDriverBy::id('HOOK_PAYMENT');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($hookPayment);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        if ($verifySimulator) {
            //TODO UNCOMMENT THIS WHEN ORDERS HAVE SIMULATOR
            /*
            $pmtSimulator = WebDriverBy::className('PmtSimulator');
            $condition = WebDriverExpectedCondition::presenceOfElementLocated($pmtSimulator);
            $this->webDriver->wait()->until($condition);
            $this->assertTrue((bool)$condition);
            */
        }
    }

    /**
     * @requires goToProduct
     *
     * @throws \Exception
     */
    public function addProduct()
    {
        $addToCartSearch = WebDriverBy::id('add_to_cart');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($addToCartSearch);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $this->webDriver->findElement($addToCartSearch)->click();
        $shoppingCartSearch = WebDriverBy::id('shopping_cart');
        $this->webDriver->findElement($shoppingCartSearch)->click();
        $shoppingCartTitle = WebDriverBy::id('cart_title');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($shoppingCartTitle);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
    }

    /**
     * @param bool $verifySimulator
     *
     * @throws \Exception
     */
    public function goToProduct($verifySimulator = true)
    {
        $this->webDriver->get(self::MAGENTO_URL);
        $this->findById('header_logo')->click();
        $featuredProductCenterSearch = WebDriverBy::id('featured-products_block_center');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($featuredProductCenterSearch);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $product = $featuredProductCenterSearch->className('s_title_block');
        $this->webDriver->findElement($product)->click();
        if ($verifySimulator) {
            //TODO UNCOMMENT THIS WHEN ORDERS HAVE SIMULATOR
            /*
            $pmtSimulator = WebDriverBy::className('PmtSimulator');
            $condition = WebDriverExpectedCondition::presenceOfElementLocated($pmtSimulator);
            $this->webDriver->wait()->until($condition);
            $this->assertTrue((bool)$condition);
            */
        }
    }

    /**
     * @throws \Exception
     */
    public function verifyUTF8()
    {
        $paymentFormElement = WebDriverBy::className('FieldsPreview-desc');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($paymentFormElement);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $this->assertSame(
            $this->configuration['firstname'] . ' ' . $this->configuration['lastname'],
            $this->findByClass('FieldsPreview-desc')->getText()
        );
    }

    /**
     * Verify Paylater iframe
     *
     * @throws \Exception
     */
    public function verifyPaylater()
    {
        $paylaterCheckout = WebDriverBy::className('paylater-checkout');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($paylaterCheckout);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $this->webDriver->findElement($paylaterCheckout)->click();
        $paylaterModal = WebDriverBy::id('module-paylater-payment');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($paylaterModal);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $modulePayment = $this->webDriver->findElement($paylaterModal);
        $firstIframe = $modulePayment->findElement(WebDriverBy::tagName('iframe'));
        $condition = WebDriverExpectedCondition::frameToBeAvailableAndSwitchToIt($firstIframe);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $pmtModal = WebDriverBy::id('pmtmodal');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($pmtModal);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $iFrame = 'pmtmodal_iframe';
        $condition = WebDriverExpectedCondition::frameToBeAvailableAndSwitchToIt($iFrame);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $paymentFormElement = WebDriverBy::name('form-continue');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($paymentFormElement);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition);
        $this->assertContains(
            'compra',
            $this->findByClass('Form-heading1')->getText()
        );
    }
}