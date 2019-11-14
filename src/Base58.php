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
use Comely\DataTypes\BcNumber;
use FurqanSiddiqui\Base58\Result\Base58Encoded;

/**
 * Class Base58
 * @package FurqanSiddiqui\Base58
 */
class Base58
{
    public const CHARSET = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";

    /**
     * @param string|null $charset
     * @return string
     */
    private static function Charset(?string $charset = null): string
    {
        $charset = $charset ?? self::CHARSET;
        if (strlen($charset) !== 58) {
            throw new \LengthException('Base58 charsets must have exactly 58 digits');
        }

        return $charset;
    }

    /**
     * @param BcNumber $decs
     * @param string|null $charset
     * @return Base58Encoded
     */
    public static function Encode(BcNumber $decs, ?string $charset = null): Base58Encoded
    {
        $base58 = BaseConvert::fromBase10($decs, self::Charset($charset));
        $base58Encoded = new Base58Encoded($base58);
        $base58Encoded->readOnly(true); // Set to read-only
        return $base58Encoded;
    }

    /**
     * @param Base58Encoded $encoded
     * @param string|null $charset
     * @return BcNumber
     */
    public static function Decode(Base58Encoded $encoded, ?string $charset = null): BcNumber
    {
        return BaseConvert::toBase10($encoded->value(), self::Charset($charset));
    }

    /**
     * @param string $encoded
     * @param string|null $charset
     * @return BcNumber
     */
    public static function DecodeFromString(string $encoded, ?string $charset = null): BcNumber
    {
        return self::Decode(new Base58Encoded($encoded), self::Charset($charset));
    }
}