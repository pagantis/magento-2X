<?php

namespace DigitalOrigin\Test\Install;

use DigitalOrigin\Test\Common\AbstractMg21Selenium;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverBy;

/**
 * Class PaylaterMgInstallTest
 * @package DigitalOrigin\Test\Install
 *
 * @group magento-install
 */
class PaylaterMgInstallTest extends AbstractMg21Selenium
{
    /**
     * @throws \Exception
     */
    public function testPaylaterMg21InstallTest()
    {
        $this->loginToBackOffice();
        $this->getPaylaterBackOffice();
        $this->configurePaylater();
        $this->quit();
    }

    /**
     * @require getPaylaterBackOffice
     *
     * @throws \Exception
     */
    private function configurePaylater()
    {
        $verify = WebDriverBy::id('payment_us_paylater_public_key');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, "PR5");

        $verify = WebDriverBy::id('payment_us_paylater_secret_key');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, "PR5");

        $activeSelect = $this->findById('payment_us_paylater_active');
        $activeOptions = $activeSelect->findElements(WebDriverBy::xpath('option'));
        foreach ($activeOptions as $activeOption) {
            if ($activeOption->getText() == 'Yes') {
                $activeOption->click();
                break;
            }
        }
        $this->findById('payment_us_paylater_public_key')->clear()->sendKeys($this->configuration['publicKey']);
        $this->findById('payment_us_paylater_secret_key')->clear()->sendKeys($this->configuration['secretKey']);

        $this->findById('payment_us_paylater_title')->clear()->sendKeys($this->configuration['methodName']);
        $this->webDriver->findElement(WebDriverBy::id('payment_us_paylater_product_simulator'))
                        ->findElement(WebDriverBy::cssSelector("option[value='".$this->configuration['defaultSimulatorOpt']."']"))
                        ->click();
        $this->webDriver->findElement(WebDriverBy::id('payment_us_paylater_checkout_simulator'))
                        ->findElement(WebDriverBy::cssSelector("option[value='".$this->configuration['defaultSimulatorOpt']."']"))
                        ->click();
        $this->findById('config-edit-form')->submit();

        $validatorSearch = WebDriverBy::className('message-success');
        $actualString = $this->webDriver->findElement($validatorSearch)->getText();
        $compareString = (strstr($actualString, 'You saved the configuration')) === false ? false : true;
        $this->assertTrue($compareString, $actualString);

        $enabledModule = $this->findByCss("select#payment_us_paylater_active > option[selected]");
        $this->assertEquals($enabledModule->getText(), 'Yes', 'PR6');

        $verify = WebDriverBy::id('payment_us_paylater_active');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, "PR7");

        $verify = WebDriverBy::id('payment_us_paylater_display_mode');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, "PR8");

        $verify = WebDriverBy::id('payment_us_paylater_product_simulator');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, "PR9");

        $verify = WebDriverBy::id('payment_us_paylater_checkout_simulator');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, "PR10");

        $verify = WebDriverBy::id('payment_us_paylater_min_installments');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, "PR11");
        $simulatorElement = $this->findById('payment_us_paylater_min_installments');
        $minInstallments = $simulatorElement->getAttribute('value');
        $this->assertEquals($minInstallments, $this->configuration['defaultMinIns'], "PR11");

        $verify = WebDriverBy::id('payment_us_paylater_max_installments');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, "PR11");
        $simulatorElement = $this->findById('payment_us_paylater_max_installments');
        $maxInstallments = $simulatorElement->getAttribute('value');
        $this->assertEquals($maxInstallments, $this->configuration['defaultMaxIns'], "PR11");

        $verify = WebDriverBy::id('payment_us_paylater_min_amount');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, "PR12");

        $verify = WebDriverBy::id('payment_us_paylater_title');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, "PR14");

        $verify = WebDriverBy::id('payment_us_paylater_ok_url');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, "PR15");

        $verify = WebDriverBy::id('payment_us_paylater_ko_url');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, "PR15");
    }
}
