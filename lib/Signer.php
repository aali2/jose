<?php

namespace SpomkyLabs\Jose;

use Base64Url\Base64Url;
use Jose\JWKInterface;
use Jose\JWTInterface;
use Jose\JWKSetInterface;
use Jose\SignerInterface;
use Jose\JSONSerializationModes;
use Jose\Operation\SignatureInterface;
use Jose\SignatureInstructionInterface;

/**
 * Class representing a JSON Web Token Manager.
 */
abstract class Signer implements SignerInterface
{
    use PayloadConverter;

    /**
     * @return \Jose\JWAManagerInterface
     */
    abstract protected function getJWAManager();

    /**
     * @param array|JWKInterface|JWKSetInterface|JWTInterface|string $input         The input to sign
     * @param array                                                  $instructions  Signature instructions
     * @param string                                                 $serialization Serialization Overview
     *
     * @return string[]|string
     */
    public function sign($input, array $instructions, $serialization = JSONSerializationModes::JSON_COMPACT_SERIALIZATION)
    {
        $this->checkInput($input);
        $this->checkInstructions($instructions);

        /*
         * @var string
         */
        $jwt_payload = Base64Url::encode($input->getPayload());

        $signatures = array(
            'payload' => $jwt_payload,
            'signatures' => array(),
        );

        foreach ($instructions as $instruction) {
            $signatures['signatures'][] = $this->computeSignature($instruction, $input, $jwt_payload);
        }

        $prepared = Converter::convert($signatures, $serialization);

        return is_array($prepared) && count($prepared) === 1 ? current($prepared) : $prepared;
    }

    /**
     * @param \Jose\SignatureInstructionInterface $instruction
     * @param \Jose\JWTInterface                  $input
     * @param string                              $jwt_payload
     *
     * @return array
     */
    protected function computeSignature(SignatureInstructionInterface $instruction, JWTInterface $input, $jwt_payload)
    {
        $protected_header   = array_merge($input->getProtectedHeader(), $instruction->getProtectedHeader());
        $unprotected_header = array_merge($input->getUnprotectedHeader(), $instruction->getUnprotectedHeader());
        $complete_header = array_merge($protected_header, $protected_header);

        $jwt_protected_header = empty($protected_header) ? null : Base64Url::encode(json_encode($protected_header));

        $signature_algorithm = $this->getSignatureAlgorithm($complete_header);

        $signature = $signature_algorithm->sign($instruction->getKey(), $jwt_protected_header.'.'.$jwt_payload);

        $jwt_signature = Base64Url::encode($signature);

        $result = array(
            'signature' => $jwt_signature,
        );
        if (!is_null($protected_header)) {
            $result['protected'] = $jwt_protected_header;
        }
        if (!empty($unprotected_header)) {
            $result['header'] = $unprotected_header;
        }

        return $result;
    }

    /**
     * @param array $complete_header The complete header
     *
     * @return \Jose\Operation\SignatureInterface
     */
    protected function getSignatureAlgorithm(array $complete_header)
    {
        if (!array_key_exists('alg', $complete_header)) {
            throw new \InvalidArgumentException("No 'alg' parameter set in the header or the key.");
        }

        $signature_algorithm = $this->getJWAManager()->getAlgorithm($complete_header['alg']);
        if (!$signature_algorithm instanceof SignatureInterface) {
            throw new \InvalidArgumentException("The algorithm '{$complete_header['alg']}' is not supported.");
        }

        return $signature_algorithm;
    }

    protected function checkInstructions(array $instructions)
    {
        if (empty($instructions)) {
            throw new \InvalidArgumentException('No instruction.');
        }
        foreach ($instructions as $instruction) {
            if (!$instruction instanceof SignatureInstructionInterface) {
                throw new \InvalidArgumentException('Bad instruction. Must implement SignatureInstructionInterface.');
            }
        }
    }
}