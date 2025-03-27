<?php

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Vliz\TemplatingClient\RequestResponseUtils;
use Vliz\TemplatingClient\ResponseException;

class RequestResponseUtilsTest extends TestCase
{
    function testFormatFromUrlParam()
    {
        $_GET['format'] = "json";
        unset($_SERVER['HTTP_ACCEPT']);
        $format = RequestResponseUtils::determineFormat();
        Assert::assertEquals("json", $format);
    }

    function testFormatFromHeader()
    {
        unset($_GET['format']);
        $_SERVER['HTTP_ACCEPT'] = "application/ld+json; charset=utf-8;q=1.0";
        $format = RequestResponseUtils::determineFormat();
        Assert::assertEquals("jsonld", $format);
    }

    function testFormatDefault()
    {
        unset($_GET['format']);
        unset($_SERVER['HTTP_ACCEPT']);
        $format = RequestResponseUtils::determineFormat();
        Assert::assertEquals("ttl", $format);
    }

    function testFormatInvalid()
    {
        $_GET['format'] = "notavalidformat";
        unset($_SERVER['HTTP_ACCEPT']);
        try {
            RequestResponseUtils::determineFormat();
            Assert::fail("Not reachable");
        } catch (ResponseException $e) {
            Assert::assertStringContainsString("notavalidformat", $e->getMessage());
            Assert::assertEquals(400, $e->getCode());
        }
    }
}