<?php

namespace Vliz\TemplatingClient;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class TemplatingClientTest extends TestCase
{
    function testSomething()
    {
        $_ENV['BASE_REF']='https://marineinfo.org/';
        $client = new TemplatingClient("tests");
        $template = "demo.twig";
        $output = $client->render($template, "nothing");
        //$output = $client->render($template, ["_"=>"nothing","baseref"=>"ksdlfjksql"]);

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
}
