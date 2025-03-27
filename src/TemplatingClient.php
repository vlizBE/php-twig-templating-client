<?php

namespace Vliz\TemplatingClient;

use Exception;
use Twig\Environment;
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
     * @throws ResponseException if anything goes wrong
     */
    public function render(string|TemplateWrapper $template, array $data): string
    {
        try {
            return $this->twig->render($template, $data);
        } catch (Exception $e) {
            $response = "Internal Error\n";
            if ($this->twig->isDebug()) {
                $response .= "Exception:\n" . var_export($e, true) . "\n";
                $response .= "Data:\n" . var_export($data, true) . "\n";
            }
            throw new ResponseException($response, 500);
        }
    }

    /**
     * @return Environment
     */
    public function getTwigEnvironment(): Environment
    {
        return $this->twig;
    }
}