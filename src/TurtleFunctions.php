<?php

namespace Vliz\TemplatingClient;

use Rize\UriTemplate;
use Twig\Environment;
use Twig\Markup;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TurtleFunctions
{
    public static function extendTwig(Environment $twig): void
    {
        $templateFormatter = new TemplateFormatter();

        $twig->addFunction(new TwigFunction('uritexpand', function ($template, $context) {
            $uri = new UriTemplate();
            return $uri->expand($template, (array)$context);
        }));

        $twig->addFilter(new TwigFilter('xsd', function ($content, $type_name, $quote = "'") use ($templateFormatter) {
            $formattedContent = $templateFormatter->format($content, $type_name, $quote);
            return new Markup($formattedContent, 'UTF-8');
        }));

        $twig->addFunction(new TwigFunction('baseref', function () {
            return $_ENV['BASE_REF'];
        }));

        $twig->addFilter(new TwigFilter('uri', function ($content) use ($templateFormatter) {
            return $templateFormatter->uriFilter($content);
        }));

        //ml: filter to fix urls without a protocol [IMIS-1635]
        $twig->addFilter(new TwigFilter('fixUrl', function ($content) {
            if (!$content or trim($content) == "") {
                return "";
            }
            $link = $content;
            if (!str_starts_with($content, 'http://') and !str_starts_with($content,
                    'ftp://') and !str_starts_with($content, 'https://')) {
                $link = "http://" . $content;
            }
            return $link;
        }));
    }

}