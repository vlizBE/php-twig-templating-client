<?php

namespace Vliz\TemplatingClient;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;

/**
 * Class TemplateFormatter originated from https://github.com/vliz-be-opsci/py-sema
 */
class TemplateFormatter
{
    /**
     * @throws Exception
     */
    public function format($content, $typeName, $quote = "'"): string
    {
        $suffix = null;
        if (str_starts_with($typeName, '@')) {
            $suffix = $typeName;
            $typeName = 'xsd:string';
        }
        return match (mb_strtolower(str_replace('xsd:', '', $typeName))) {
            'boolean' => $this->formatBoolean($content, $quote),
            'integer' => $this->formatInteger($content, $quote),
            'double' => $this->formatDouble($content, $quote),
            'date' => $this->formatDate($content, $quote),
            'datetime' => $this->formatDateTime($content, $quote),
            'anyuri' => $this->formatURI($content, $quote),
            'string' => $this->formatString($content, $quote, $suffix),
            'gyear', 'year', 'yyyy' => $this->formatGYear($content, $quote),
            'gyearmonth', 'year-month', 'yyyy-mm' => $this->formatGYearMonth($content, $quote),
            'auto-date' => $this->autoDate($content, $quote),
            'auto-number' => $this->autoNumber($content, $quote),
            'auto-any', 'auto' => $this->autoAny($content, $quote),
            default => $content,
        };
    }

    private function value($content, $quote, $typeName, $suffix = null): string
    {
        if (is_null($suffix)) {
            $suffix = '^^' . $typeName;
        }
        return $quote . $content . $quote . $suffix;
    }


    private function formatBoolean($content, $quote): string
    {
        // make rigid bool
        if (!is_bool($content)) {
            $content = !in_array(strtolower((string) $content), ['', '0', 'no', 'false', 'off']);
        }
        // serialize to string again
        return $this->value(strtolower((string) $content), $quote, 'xsd:boolean');
    }

    private function formatInteger($content, $quote): string
    {
        // make rigid int
        if (!is_int($content)) {
            $content = (int) $content;
        }
        // serialize to string again
        return $this->value((string) $content, $quote, 'xsd:integer');
    }

    private function formatDouble($content, $quote): string
    {
        // make rigid double
        if (!is_float($content)) {
            $content = (float) $content;
        }
        // serialize to string again
        return $this->value((string) $content, $quote, 'xsd:double');
    }

    private function formatDate($content, $quote): string
    {
        // try to parse, otherwise just return the value
        if (strtotime($content)) {
            $content = date($content);
        }
        return $this->value($content, $quote, 'xsd:date');
    }

    private function formatDateTime($content, $quote): string
    {
        // try to parse, otherwise just return the value
        if (strtotime($content)) {
            $content = date($content);
        }
        return $this->value($content, $quote, 'xsd:dateTime');
    }

    private function cleanURI($uri): string
    {
        $uri = str_replace(['[', ']', '<', '>'], ['%5B', '%5D', '%3C', '%3E'], $uri);
        if ($this->isValidUri($uri)) {
            return $uri;
        } else {
            return rawurlencode($uri);
        }
    }

    private function formatURI($content, $quote): string
    {
        $uri = $this->cleanURI($content);
        return $this->value($uri, $quote, 'xsd:anyURI');
    }

    private function isValidUri($uri): bool
    {
        // Checks if the URI starts with "urn:"
        if (str_starts_with($uri, "urn:")) {
            // If so, prepend "http://make.safe/" to the URI
            $uri = "http://make.safe/" . $uri;
        }
        // Check if the URI is a valid URL using filter_var with FILTER_VALIDATE_URL
        return (bool) filter_var($uri, FILTER_VALIDATE_URL);
    }

    private function formatString($content, $quote, $suffix): string
    {
        // apply escape sequences: \ to \\ and quote to \quote
        $escqt = "\\" . $quote;
        $content = str_replace("\\", "\\\\", strval($content));
        $content = str_replace($quote, $escqt, $content);

        if (str_contains($content, "\n")) {
            $quote = str_repeat($quote, 3);  // make long quote variant to allow for newlines
            if (str_contains($content, $quote)) {
                throw new Exception(
                    "ttl format error: still having quote {$quote} in text content {$content} applied quote format {$quote} in text content"
                );
            }
        }

        return $this->value($content, $quote, "xsd:string", $suffix);
    }

    public function uriFilter($uri): string
    {
        //ml: escape square brackets in URIs [IMIS-1505]
        $uri = $this->cleanURI($uri);
        return "<{$uri}>";
    }

    private function formatGYear($content, $quote): string
    {
        // make rigid gYear
        if ($content instanceof DateTimeInterface) {
            $year = $content->format('Y');  // extract year from date
        } else {  // other input types handled
            $content = trim((string) $content);  // via trimmed string
            $year = (int) $content;  // converted to int
        }
        // we should be sure of int now
        // see https://www.datypic.com/sc/xsd11/t-xsd_gYear.html
        // for examples of correct value formatting
        $content = sprintf('%s%04d', $year < 0 ? '-' : '', abs($year));
        return $this->value($content, $quote, 'xsd:gYear');
    }

    private function formatGYearMonth($content, $quote): string
    {
        // make rigid gYearMonth
        if ($content instanceof DateTimeInterface) {
            $year = $content->format('Y');
            $month = $content->format('m');
        } else {
            $content = trim((string) $content);
            $sign = 1;
            if ($content[0] === '-') {
                $sign = -1;
                $content = substr($content, 1);
            }
            list($year, $month) = explode('-', $content);
            $year = (int) $year * $sign;
            $month = (int) $month;
        }
        // see https://www.datypic.com/sc/xsd11/t-xsd_gYearMonth.html
        // for examples of correct value formatting
        $content = sprintf('%s%04d-%02d', $year < 0 ? '-' : '', abs($year), $month);
        return $this->value($content, $quote, 'xsd:gYearMonth');
    }

    /**
     * @throws Exception
     */
    private function autoDate($content, $quote): string
    {
        // infer type from input + apply formatting according to fallback-scenario
        // 1. type DateTime
        if ($content instanceof DateTimeInterface) {
            return $this->formatDateTime($content, $quote);
        }
        // 2. type Date
        if ($content instanceof DateTimeImmutable) {
            return $this->formatDate($content, $quote);
        }
        // 3. string parseable to datetime
        // 4. string parseable to date
        // 5. string matching [-]?YYYY-MM for gyearmonth
        // 6. string matching [-]?YYYY for gyear
        $formattedDate = $this->autoStrToFormattedDate((string) $content, $quote);
        if ($formattedDate !== null) {
            return $formattedDate;
        }
        // 7. int for gyear
        if (is_int($content)) {
            return $this->formatGYear($content, $quote);
        }
        // 8. anything else should raise an error
        throw new Exception("auto-date format failed to infer date type");
    }

    private function autoStrToFormattedDate($content, $quote): ?string
    {
        $patterns = [
            '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/' => 'formatDateTime',
            '/\d{4}-\d{2}-\d{2}/' => 'formatDate',
            '/\d{4}-\d{2}/' => 'formatGYearMonth',
            '/\d{4}/' => 'formatGYear',
        ];

        foreach ($patterns as $pattern => $formatter) {
            if (preg_match($pattern, $content)) {
                try {
                    new \DateTime($content);
                    return $this->$formatter($content, $quote);
                } catch (Exception $e) {
                    // continue to next pattern
                }
            }
        }
        return null;
    }

    /**
     * @throws Exception
     */
    private function autoNumber($content, $quote): string
    {
        // infer type from input + apply formatting according to fallback-scenario
        // 1. type int
        if (is_int($content)) {
            return $this->formatInteger($content, $quote);
        }
        // 2. type float
        if (is_float($content)) {
            return $this->formatDouble($content, $quote);
        }
        // 3. string parseable to int
        // 4. string parseable to float
        $formattedNumber = $this->autoStrToFormattedNumber((string) $content, $quote);
        if ($formattedNumber !== null) {
            return $formattedNumber;
        }
        // 5. anything else should raise an error
        throw new Exception("auto-number format failed to infer number type");
    }

    private function autoStrToFormattedNumber($content, $quote): ?string
    {
        $testContent = strtolower(trim($content));
        if (in_array($testContent[0], ['-', '+'])) {
            $testContent = substr($testContent, 1);
        }
        if (ctype_digit($testContent)) {
            return $this->formatInteger($content, $quote);
        }
        if (ctype_digit(str_replace('.', '', $testContent))) {
            return $this->formatDouble($content, $quote);
        }
        return null;
    }

    /**
     * @throws Exception
     */
    private function autoAny($content, $quote): string
    {
        // infer type from input + apply formatting according to fallback-scenario
        // 1. type bool
        if (is_bool($content)) {
            return $this->formatBoolean($content, $quote);
        }
        // 2. type int
        if (is_int($content)) {
            return $this->formatInteger($content, $quote);
        }
        // 3. type float
        if (is_float($content)) {
            return $this->formatDouble($content, $quote);
        }
        // 4. type DateTime
        if ($content instanceof DateTimeInterface) {
            return $this->formatDateTime($content, $quote);
        }
        // 5. type Date
        if ($content instanceof DateTimeImmutable) {
            return $this->formatDate($content, $quote);
        }
        // 6. string parseable to exact bool true or false (ignoring case)
        if (in_array(strtolower(trim((string) $content)), ['true', 'false'])) {
            return $this->formatBoolean($content, $quote);
        }
        // 7. string parseable to int
        // 8. string parseable to float
        $formattedNumber = $this->autoStrToFormattedNumber((string) $content, $quote);
        if ($formattedNumber !== null) {
            return $formattedNumber;
        }
        // 9. string parseable to datetime
        // 10. string parseable to date
        // 11. string matching [-]?YYYY-MM for gyearmonth
        // 12. string matching [-]?YYYY for gyear
        $formattedDate = $this->autoStrToFormattedDate((string) $content, $quote);
        if ($formattedDate !== null) {
            return $formattedDate;
        }
        // 13. string is valid uri
        if ($this->isValidUri($this->cleanURI((string) $content))) {
            return $this->formatURI($content, $quote);
        }
        // 14. remaining string content
        return $this->formatString($content, $quote, null);
    }
}
