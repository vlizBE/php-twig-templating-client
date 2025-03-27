<?php

namespace Vliz\TemplatingClient;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class TemplatingClientTest extends TestCase
{
    function testSomething()
    {
        $_ENV['BASE_REF'] = 'https://marineinfo.org/';
        $client = new TemplatingClient("tests");
        $template = "demo.twig";
        $output = $client->render($template, ["_" => "nothing"]);

        // Assert INPUT: and OUTPUT: lines are the same
        $lineno = 0;
        foreach (preg_split("/[\r\n]/", $output) as $line) {
            $lineno++;
            if (str_starts_with($line, "INPUT:")) {
                $lastInput = explode(":", $line, 2)[1];
            } elseif (str_starts_with($line, "OUTPUT:")) {
                $lastOutput = explode(":", $line, 2)[1];
                Assert::assertEquals($lastOutput, $lastInput, "Error in $template : $lineno");
            }
        }
    }

    function testCrashNoDebug()
    {
        $client = new TemplatingClient("tests", ["debug" => false]);
        $output = $client->render("thisdoesnotexist.twig", []);
        Assert::assertEquals(500, http_response_code());
        Assert::assertEquals("Internal Error\n", $output);
    }

    function testCrashWithDebug()
    {
        $client = new TemplatingClient("tests", ["debug" => true]);
        $output = $client->render("thisdoesnotexist.twig", []);
        Assert::assertEquals(500, http_response_code());
        Assert::assertStringContainsString("Exception", $output);
    }

}
