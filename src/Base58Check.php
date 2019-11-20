<?php
/**
 * This file is a part of "furqansiddiqui/base58check-php" package.
 * https://github.com/furqansiddiqui/base58check-php
 *
 * Copyright (c) 2019 Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/furqansiddiqui/base58check-php/blob/master/LICENSE
 */

declare(strict_types=1);

namespace FurqanSiddiqui\Base58;

use Comely\DataTypes\BcMath\BaseConvert;
use Comely\DataTypes\Buffer\Base16;
use Comely\DataTypes\Buffer\Binary;
use FurqanSiddiqui\Base58\Result\Base58Encoded;

/**
 * Class Base58Check
 * @package FurqanSiddiqui\Base58
 */
class Base58Check
{
    public const CHECKSUM_BYTES = 4;

    /** @var null|string */
    private $charset;
    /** @var null|int */
    private $checksumBytes;
    /** @var null|callable */
    private $checksumCalculateFunc;

    /**
     * @param string $charset
     * @return Base58Check
     */
    public function charset(string $charset): self
    {
        if (strlen($charset) !== 58) {
            throw new \LengthException('Base58 charsets must have exactly 58 digits');
        }

        $this->charset = $charset;
        return $this;
    }

    /**
     * @param int $bytes
     * @param callable|null $checksumCalculateFunc
     * @return Base58Check
     */
    public function checksum(int $bytes, ?callable $checksumCalculateFunc = null): self
    {
        if ($bytes < 0) {
            throw new \InvalidArgumentException('Checksum bytes must be positive integer');
        }

        $this->checksumBytes = $bytes;
        $this->checksumCalculateFunc = $checksumCalculateFunc;
        return $this;
    }

    /**
     * @param $encoded
     * @param bool $convertLeadingOnes
     * @return Base16
     */
    public function decode($encoded, bool $convertLeadingOnes = true): Base16
    {
        if (!$encoded instanceof Base58Encoded) {
            if (!is_string($encoded) || !$encoded) {
                throw new \InvalidArgumentException('Base58check decode method expects Base58Encoded buffer or String as an argument');
            }

            $encoded = new Base58Encoded($encoded);
        }

        $encoded = $encoded->value();
        // Convert leading ones to 00s?
        if ($convertLeadingOnes) {
            $leadingOnes = strlen($encoded) - strlen(ltrim($encoded, "1"));
            $leadingOnes = intval($leadingOnes);
        }

        $base58Charset = $this->charset ?? Base58::CHARSET;
        $base58Decode = BaseConvert::toBase10($encoded, $base58Charset);
        $data = BaseConvert::fromBase10($base58Decode, BaseConvert::CHARSET_BASE16);

        $checksumLen = $this->checksumBytes ?? self::CHECKSUM_BYTES;
        if ($checksumLen > 0) {
            $checksumHexits = $checksumLen * 2;
            $checksum = substr($data, -1 * $checksumHexits);
            $data = substr($data, 0, -1 * $checksumHexits);
        }

        if (isset($leadingOnes) && $leadingOnes > 0) {
            $data = str_repeat("00", $leadingOnes) . $data;
        }

        $data = new Base16($data);
        if (isset($checksum)) {
            if ($this->checksumCalculateFunc) {
                $validateChecksum = call_user_func_array($this->checksumCalculateFunc, [$data->copy()]);
                if (!$validateChecksum instanceof Binary) {
                    throw new \UnexpectedValueException('Base58Check checksum compute callback must return datatype Binary');
                }
            } else {
                $validateChecksum = $data->binary()->hash()
                    ->digest("sha256", 2, $checksumLen); // 2 iterations of SHA256, get N bytes from final iteration
            }

            if (!hash_equals($checksum, $validateChecksum->base16()->hexits())) {
                throw new \UnexpectedValueException('Base58check decoded checksum does not match');
            }
        }

        return $data;
    }

    /**
     * @param Base16|string $hexits
     * @return Base58Encoded
     */
    public function encode($hexits): Base58Encoded
    {
        if ($hexits instanceof Base16) {
            $buffer = $hexits;
            $hexits = $buffer->hexits(false);
        } else {
            if (!preg_match('/^(0x)?[a-f0-9]+$/i', $hexits)) {
                throw new \InvalidArgumentException('Only hexadecimal numbers can be decoded');
            }

            if (substr($hexits, 0, 2) === "0x") {
                $hexits = substr($hexits, 2);
            }

            $buffer = new Base16($hexits);
        }

        $checksumBytes = $this->checksumBytes ?? self::CHECKSUM_BYTES;
        if ($this->checksumCalculateFunc) {
            $checksum = call_user_func_array($this->checksumCalculateFunc, [$buffer->copy()]);
            if (!$checksum instanceof Binary) {
                throw new \UnexpectedValueException('Base58Check checksum compute callback must return datatype Binary');
            }
        } else {
            $checksum = $buffer->binary()->hash()
                ->digest("sha256", 2, $checksumBytes); // 2 iterations of SHA256, get N bytes from final iteration
        }

        // Verify checksum length in bytes
        if ($checksum->size()->bytes() !== $checksumBytes) {
            throw new \UnexpectedValueException(
                sprintf('Base58Check checksum must be precisely %d bytes long, got %d bytes', $checksumBytes, $checksum->size()->bytes())
            );
        }

        $buffer->append($checksum->base16()); // Append checksum to passed binary data
        $leadingZeros = strlen($hexits) - strlen(ltrim($hexits, "0"));
        $leadingZeros = intval($leadingZeros / 2);

        $hex2dec = BaseConvert::toBase10($buffer->hexits(false), BaseConvert::CHARSET_BASE16, false);
        $base58Charset = $this->charset ?? Base58::CHARSET;
        $base58Encoded = BaseConvert::fromBase10($hex2dec, $base58Charset);
        if ($leadingZeros) {
            $base58Encoded = str_repeat("1", $leadingZeros) . $base58Encoded;
        }

        $base58Encoded = new Base58Encoded($base58Encoded);
        $base58Encoded->readOnly(true); // Read-only
        return $base58Encoded;
    }
}