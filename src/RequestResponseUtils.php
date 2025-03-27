<?php

namespace Vliz\TemplatingClient;

use EasyRdf\Format;
use EasyRdf\Graph;
use Exception;
use Twig\TemplateWrapper;

/**
 * A few utilities to integrate with EasyRDF
 */
class RequestResponseUtils
{
    /**
     * Find out the requested format from either the ?format=XXX request parameter or the HTTP Accept header.
     * @param array List of supported formats
     * @return string a valid EasyRDF Format
     */
    public static function determineFormat(
        array $supportedFormats = ['ttl', 'json', 'jsonld', 'ntriples', 'turtle', 'rdfxml', 'n3']
    ): string {
        $formatUrlParam = $_GET['format'] ?? null;
        if (is_null($formatUrlParam)) {
            // Parse https://httpwg.org/specs/rfc9110.html#field.accept
            $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? "";
            $acceptMediaRanges = explode(",", $acceptHeader);
            foreach ($acceptMediaRanges as $acceptMediaRange) {
                if (!is_null($formatUrlParam)) {
                    break;
                }
                $parameters = explode(";", $acceptMediaRange);
                $mediaType = trim($parameters[0]);
                switch ($mediaType) {
                    case "text/turtle":
                        $formatUrlParam = "ttl";
                        break;
                    case "application/ld+json":
                        $formatUrlParam = "jsonld";
                        break;
                    case "application/rdf+xml":
                        $formatUrlParam = "rdfxml";
                        break;
                    case "text/html": //FIXME We need more templates
                        $formatUrlParam = "ttl";
                        break;
                    default:
                        break;
                }
            }
            if (is_null($formatUrlParam)) {
                $formatUrlParam = "ttl";
            }
        }

        //We walk through all the Format::register options and take all the non/broken ones
        // case 'dot' is broken, tons of deprecations
        // case 'json-triples','rdfa','sparql-xml' is broken, no serialiser class available
        // case 'png','gif','svg' is broken, deprecations and requires dot being installed
        if (!in_array($formatUrlParam, $supportedFormats)) {
            throw new ResponseException("Unsupported format: $formatUrlParam", 400/*Bad request*/);
        }
        return $formatUrlParam;
    }

    /**
     * Use the templatingclient to render the response to HTTP, Including headers
     *
     * Expected usage is:
     * try {
     *     $formatName=RequestResponseUtils::getFormat();
     *     $client=new TemplatingClient("/path/to/twig/templates")
     *     $data=["whatever"=>"data","you"=>"need]
     *     RequestResponseUtils::respond($client,"sometemplate.twig",$data,$formatName)
     * } catch(ResponseException $e){
     *     e.render();
     * }
     *
     * @param TemplatingClient $client
     * @param string|TemplateWrapper $template
     * @param array $data
     * @param string $formatName
     * @return void
     */
    public static function respond(
        TemplatingClient $client,
        string|TemplateWrapper $template,
        array $data,
        string $formatName
    ): void {
        $turtleResponse = $client->render($template, $data);
        if ($formatName === "ttl") {
            $response = $turtleResponse;
            $mimeType = "text/turtle;charset=utf-8";
        } else {
            try {
                $graph = new Graph();
                $graph->parse($turtleResponse, 'turtle');
                $formatObj = Format::getFormat($formatName);
                $mimeType = $formatObj->getDefaultMimeType();
                $response = $graph->serialise($formatObj);
            } catch (Exception $e) {
                $msg = "Inetnal error\n";
                if ($client->getTwigEnvironment()->isDebug()) {
                    $msg .= "Exception: " . $e->getMessage() . "\n";
                    $msg .= "Data: " . var_export($data, true) . "\n";
                }
                throw new ResponseException($msg, 500, $e);
            }
        }

        // Headers are set only after parse/serialize: If anything errors, don't cache the error
        if (!is_null($mimeType)) {
            header('content-Type: ' . $mimeType . '; charset=UTF-8');
        }
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: *');
        header('Access-Control-Allow-Headers: * ');
        header('cache-control: public, max-age=3600');
        header('Vary: Accept');
        echo $response;
    }
}