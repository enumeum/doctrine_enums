<?php declare(strict_types=1);

namespace Enumeum\DoctrineEnum\Tools;

class CommentMarker
{
    private const COMMENT_MARK = '(EnumValuesChanged)';

    public static function mark(?string $comment): ?string
    {
        return $comment ? $comment . self::COMMENT_MARK : $comment;
    }

    public static function unmark(?string $comment): ?string
    {
        if ($comment !== null && false !== mb_stripos($comment, self::COMMENT_MARK)) {
            return str_replace(self::COMMENT_MARK, '', $comment);
        }

        return $comment;
    }
}
