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

use FurqanSiddiqui\Base58\Result\Base58Encoded;
use FurqanSiddiqui\BcMath\BcBaseConvert;
use FurqanSiddiqui\DataTypes\Base16;
use FurqanSiddiqui\DataTypes\Binary;

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
     * @param string $hexits
     * @return Base58Encoded
     */
    public function encode(string $hexits): Base58Encoded
    {
        if (!preg_match('/^(0x)?[a-f0-9]+$/i', $hexits)) {
            throw new \InvalidArgumentException('Only hexadecimal numbers can be decoded');
        }

        if (substr($hexits, 0, 2) === "0x") {
            $hexits = substr($hexits, 2);
        }

        $buffer = new Base16($hexits);
        $checksumBytes = $this->checksumBytes ?? self::CHECKSUM_BYTES;
        if ($this->checksumCalculateFunc) {
            $checksum = call_user_func_array($this->checksumCalculateFunc, [$buffer->copy()]);
            if (!$checksum instanceof Binary) {
                throw new \UnexpectedValueException('Base58Check checksum compute callback must return datatype Binary');
            }
        } else {
            $checksum = $buffer->copy();
            $checksum->hash()->digest("sha256", 2, $checksumBytes); // 2 iterations of SHA256, get last XX bytes from final iteration
        }

        // Verify checksum length in bytes
        if ($checksum->length()->bytes() !== $checksumBytes) {
            throw new \UnexpectedValueException(
                sprintf('Base58Check checksum must be precisely %d bytes long, got %d bytes', $checksumBytes, $checksum->length()->bytes())
            );
        }

        $buffer->append($checksum->raw()); // Append checksum to passed binary data
        $leadingZeros = strlen($hexits) - strlen(ltrim($hexits, "0"));
        $leadingZeros = intval($leadingZeros / 2);

        $hex2dec = BcBaseConvert::toBase10($buffer->hexits(false), BcBaseConvert::CHARSET_BASE16, false);
        $base58Charset = $this->charset ?? Base58::CHARSET;
        $base58Encoded = BcBaseConvert::fromBase10($hex2dec, $base58Charset);
        if ($leadingZeros) {
            $base58Encoded = str_repeat("1", $leadingZeros) . $base58Encoded;
        }

        $base58Encoded = new Base58Encoded($base58Encoded);
        $base58Encoded->readOnly(true); // Read-only
        return $base58Encoded;
    }
}