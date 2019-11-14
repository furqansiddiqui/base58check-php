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

use Comely\DataTypes\Buffer\AbstractBuffer;

/**
 * Class Base58Encoded
 * @package FurqanSiddiqui\Base58\Result
 */
class Base58Encoded extends AbstractBuffer
{
    /** @var string|null */
    private $charset;

    /**
     * Base58Encoded constructor.
     * @param string $encoded
     * @param string|null $charset
     */
    public function __construct(string $encoded, ?string $charset = null)
    {
        $this->charset = $charset;
        parent::__construct($encoded);
    }

    /**
     * @param string|null $data
     * @return string
     */
    public function validatedDataTypeValue(?string $data): string
    {
        if (!$data) {
            throw new \InvalidArgumentException('Base58Encoded objects cannot be constructed without data');
        }

        if ($this->charset) {
            if (!preg_match('/^[' . preg_quote($this->charset, '/') . ']+$/', $data)) {
                throw new \InvalidArgumentException('Encoded string does not match given Base58 charset');
            }
        }

        return $data;
    }
}