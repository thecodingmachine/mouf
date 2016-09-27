<?php


namespace Mouf;


class ClassNotFoundException extends \LogicException
{
    public static function notFound($className) {
        return new self('Could not find class "'.$className.'"');
    }
}