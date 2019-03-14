<?php

namespace DigitalOrigin\Pmt\Test\Register;

use DigitalOrigin\Pmt\Test\Common\AbstractMg21Selenium;

/**
 * Class pagantisMgInstallTest
 * @package DigitalOrigin\Test\Register
 *
 * @group magento-register
 */
class pagantisMgRegisterTest extends AbstractMg21Selenium
{
    /**
     * @require configurepagantis
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
