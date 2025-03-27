<?php

namespace Vliz\TemplatingClient;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Runtime\EscaperRuntime;
use Twig\TemplateWrapper;

class TemplatingClient
{
    private Environment $twig;

    /**
     * @param string|array $templatesPath One or more paths to search for templates.  A default for 'debug' is provided
     * @param array $options Options for the environment.
     */
    public function __construct(string|array $templatesPath, array $options = [])
    {
        $loader = new FilesystemLoader($templatesPath);
        $options = array_merge([
            'debug' => isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev',
            'autoescape' => 'turtle',

        ], $options);
        $this->twig = new Environment($loader, $options);

        //Add an escaper for turtle literals.
        //The 'turtle' escape mode escapes \ and " , so should be delimited by "double qoutes"
        //See https://www.w3.org/TR/turtle/#turtle-literals
        $this->twig->getRuntime(EscaperRuntime::class)->setEscaper('turtle', function ($input) {
            if (is_null($input)) {
                return "";
            }
            return strtr($input, ["\"" => "\\\"", "\\" => "\\\\", "\n" => "\\n", "\r" => "\\r"]);
        });
        if ($options['debug']) {
            $this->twig->addExtension(new DebugExtension());
        }
        TurtleFunctions::extendTwig($this->twig);
    }

    /**
     * Call Environment::render and handle errors
     *
     * @param string|TemplateWrapper $template Argument for twig->render
     * @param array $data Argument for twig->render
     * @return string Result for twig->render
     */
    public function render(string|TemplateWrapper $template, array $data): string
    {
        try {
            $output = $this->twig->render($template, $data);
        } catch (LoaderError|RuntimeError|SyntaxError $e) {
            //TODO: how best to return errors?
            http_response_code(500);
            echo $e;
            die();
        }
        return $output;
    }
}