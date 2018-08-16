<?php

namespace DigitalOrigin\Pmt\Test\Register;

use DigitalOrigin\Pmt\Test\Common\AbstractMg21Selenium;

/**
 * Class PaylaterMgInstallTest
 * @package DigitalOrigin\Test\Register
 *
 * @group magento-register
 */
class PaylaterMgRegisterTest extends AbstractMg21Selenium
{
    /**
     * @require configurePaylater
     *
     * @throws \Exception
     */
    public function testRegisterAndLogin()
    {
        $this->createAccount();
        $this->logoutFromFrontend();
        $this->loginToFrontend();
        $this->logoutFromFrontend();
        $this->quit();
    }
}
