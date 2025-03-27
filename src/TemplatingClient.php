<?php

namespace Vliz\TemplatingClient;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

class TemplatingClient
{
    private Environment $twig;

    public function __construct(string $templatesPath = null)
    {
        $loader = new FilesystemLoader($templatesPath);
        $this->twig = new Environment($loader, [
            'debug' => isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev'
        ]);
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev') {
            $this->twig->addExtension(new DebugExtension());
        }
        TurtleFunctions::extendTwig($this->twig);
    }

    public function render($template, $data)
    {
        try {
            $output = $this->twig->render($template, ['_' => $data]);
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            //TODO: how best to return errors?
            http_response_code(500);
            echo $e;
            die();
        }
        return $output;
    }
}