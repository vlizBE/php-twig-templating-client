<?php

namespace Vliz\TemplatingClient;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\Markup;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\DebugExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;
use Rize\UriTemplate;

class TemplatingClient
{
    private Environment $twig;
    private TemplateFormatter $templateFormatter;

    public function __construct(string $templatesPath = null)
    {
        $loader = new FilesystemLoader($templatesPath);
        $this->twig = new Environment($loader, [
            'debug' => isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev'
        ]);
        $this->templateFormatter = new TemplateFormatter();
    }

    public function render($template, $data)
    {
        //ml: extend twig
        $this->extendTwig();

        try {
            $output = $this->twig->render($template, ['_' => $data]);
        } catch (LoaderError | RuntimeError | SyntaxError $e) {
            //TODO: how best to return errors?
            http_response_code(500);
            echo $e;
            die();
        }
        return $output;
    }

    private function extendTwig(): void
    {
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev') $this->twig->addExtension(new DebugExtension());

        $uritexpand = new TwigFunction('uritexpand', function ($template, $context) {
            $uri = new UriTemplate();
            return $uri->expand($template, (array) $context);
        });
        $this->twig->addFunction($uritexpand);

        $xsd = new TwigFilter('xsd', function ($content, $type_name, $quote = "'") {
            $formattedContent = $this->templateFormatter->format($content, $type_name, $quote);
            return new Markup($formattedContent, 'UTF-8');
        });
        $this->twig->addFilter($xsd);

        $baseRef = new TwigFunction('baseref', function () {
            return $_ENV['BASE_REF'];
        });
        $this->twig->addFunction($baseRef);

        $uri = new TwigFilter('uri', function ($content) {
            return $this->templateFormatter->uriFilter($content);
        });
        $this->twig->addFilter($uri);

        //ml: filter to fix urls without a protocol [IMIS-1635]
        $fixUrl = new TwigFilter('fixUrl', function ($content) {
            if (!$content or trim($content) == "") return "";
            $link = $content;
            if (!str_starts_with($content, 'http://')
                and !str_starts_with($content, 'ftp://')
                and !str_starts_with($content, 'https://')
            ) $link = "http://" . $content;
            return $link;
        });
        $this->twig->addFilter($fixUrl);
    }

}