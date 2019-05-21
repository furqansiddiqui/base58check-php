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

namespace FurqanSiddiqui\Base58\Result;

use FurqanSiddiqui\Base58\Base58Check;
use FurqanSiddiqui\DataTypes\Base16;
use FurqanSiddiqui\DataTypes\Buffer\AbstractStringType;

/**
 * Class Base58Encoded
 * @package FurqanSiddiqui\Base58\Result
 */
class Base58Encoded extends AbstractStringType
{
    /**
     * Base58Encoded constructor.
     * @param string $encoded
     * @param string|null $charset
     */
    public function __construct(string $encoded, ?string $charset = null)
    {
        if (!$encoded) {
            throw new \InvalidArgumentException('Base58Encoded objects cannot be constructed without data');
        }

        if ($charset) {
            if (!preg_match('/^[' . preg_quote($charset, '/') . ']+$/', $encoded)) {
                throw new \InvalidArgumentException('Encoded string does not match given Base58 charset');
            }
        }

        parent::__construct($encoded);
    }

    /**
     * @return Base16
     */
    public function decode(): Base16
    {
        return (new Base58Check())->decode($this);
    }
}