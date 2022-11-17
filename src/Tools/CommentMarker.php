<?php

declare(strict_types=1);

/*
 * This file is part of the "Doctrine extension to manage enumerations in PostgreSQL" package.
 * (c) Alexey Sitka <alexey.sitka@gmail.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enumeum\DoctrineEnum\Tools;

class CommentMarker
{
    private const COMMENT_MARK = '(EnumValuesChanged)';

    public static function mark(?string $comment): ?string
    {
        return $comment ? $comment.self::COMMENT_MARK : $comment;
    }

    public static function unmark(?string $comment): ?string
    {
        if (null !== $comment && false !== mb_stripos($comment, self::COMMENT_MARK)) {
            return str_replace(self::COMMENT_MARK, '', $comment);
        }

        return $comment;
    }
}
