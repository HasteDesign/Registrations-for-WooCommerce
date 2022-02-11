<?php

class CheckoutCest
{
    // tests
    public function tryToTest(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->see('mundo!');
    }


}
