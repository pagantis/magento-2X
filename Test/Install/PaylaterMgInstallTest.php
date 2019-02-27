<?php

namespace DigitalOrigin\Pmt\Test\Install;

use DigitalOrigin\Pmt\Test\Common\AbstractMg21Selenium;
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
        $verify = WebDriverBy::id('payment_us_paylater_pmt_public_key');
        $condition = WebDriverExpectedCondition::visibilityOfElementLocated($verify);
        $this->webDriver->wait()->until($condition);
        $this->assertTrue((bool) $condition, "PR5");

        $verify = WebDriverBy::id('payment_us_paylater_pmt_private_key');
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
        $this->findById('payment_us_paylater_pmt_public_key')->clear()->sendKeys($this->configuration['publicKey']);
        $this->findById('payment_us_paylater_pmt_private_key')->clear()->sendKeys($this->configuration['secretKey']);

        $this->findById('payment_us_paylater_title')->clear()->sendKeys($this->configuration['methodName']);
        $simulatorCss = "option[value='".$this->configuration['defaultSimulatorOpt']."']";
        $this->webDriver->findElement(WebDriverBy::id('payment_us_paylater_product_simulator'))
                        ->findElement(WebDriverBy::cssSelector($simulatorCss))
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
    }
}
