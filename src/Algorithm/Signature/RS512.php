<?php

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2015 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Jose\Algorithm\Signature;

use phpseclib\Crypt\RSA as PHPSecLibRSA;

/**
 * Class RS512.
 */
final class RS512 extends RSA
{
    /**
     * @return string
     */
    protected function getAlgorithm()
    {
        return 'sha512';
    }

    /**
     * @return int
     */
    protected function getSignatureMethod()
    {
        return PHPSecLibRSA::SIGNATURE_PKCS1;
    }

    /**
     * @return string
     */
    public function getAlgorithmName()
    {
        return 'RS512';
    }
}