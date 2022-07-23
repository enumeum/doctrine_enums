<?php declare(strict_types=1);

namespace Enumeum\DoctrineEnum\Tools;

use Enumeum\DoctrineEnum\Exception\InvalidArgumentException;

class EnumChangesTool
{
    public static function isChanged(iterable $current, iterable $target): bool
    {
        $current = [...$current];
        foreach ($target as $order => $value) {
            if (!array_key_exists($order, $current) || $current[$order] !== $value) {
                return true;
            }
        }

        return false;
    }

    public static function getAlterAddValues(iterable $current, iterable $target): iterable
    {
        $add = [];

        $current = [...$current];
        foreach ($target as $order => $value) {
            if (array_key_exists($order, $current) && $current[$order] !== $value) {
                throw InvalidArgumentException::enumReorderingIsProhibited();
            }

            if (!array_key_exists($order, $current)) {
                $add[] = $value;
            }
        }

        return $add;
    }
}
