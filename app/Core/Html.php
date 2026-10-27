<?php
namespace App\Core;

/** Allow-list HTML sanitizer for rich text content (lesson bodies, email templates). */
class Html {
    private const TAGS = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'a', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'blockquote', 'code', 'pre', 'hr', 'img', 'span', 'div', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'figure', 'figcaption', 'sub', 'sup', 'mark'];
    private const ATTRS = ['href', 'title', 'src', 'alt', 'class', 'target', 'rel', 'width', 'height', 'colspan', 'rowspan', 'style'];

    public static function clean(?string $html): string {
        $html = trim((string)$html);
        if ($html === '') {
            return '';}
        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        $root = $doc->getElementById('__root');
        if (!$root) {
            return e(strip_tags($html));}
        self::walk($root);
        $out = '';
        foreach ($root->childNodes as $c) {
            $out .= $doc->saveHTML($c);}
        return $out;}

    private static function walk(\DOMNode $node): void {
        for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
            $c = $node->childNodes->item($i);
            if ($c instanceof \DOMElement) {
                $tag = strtolower($c->tagName);
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'textarea', 'button', 'select', 'link', 'meta', 'svg', 'math'], true)) {
                    $node->removeChild($c);
                    continue;}
                if (!in_array($tag, self::TAGS, true)) {
                    self::walk($c);
                    while ($c->firstChild) {
                        $node->insertBefore($c->firstChild, $c);}
                    $node->removeChild($c);
                    continue;}
                for ($a = $c->attributes->length - 1; $a >= 0; $a--) {
                    $attr = $c->attributes->item($a);
                    $n = strtolower($attr->nodeName);
                    $v = trim($attr->nodeValue);
                    if (!in_array($n, self::ATTRS, true)) {
                        $c->removeAttribute($attr->nodeName);
                        continue;}
                    if (in_array($n, ['href', 'src'], true) && !preg_match('#^(https?://|mailto:|/|\#|data:image/(png|jpe?g|gif|webp);)#i', $v)) {
                        $c->removeAttribute($attr->nodeName);}
                    if ($n === 'style' && preg_match('/(expression|url\s*\(|javascript:|@import|behaviou?r)/i', $v)) {
                        $c->removeAttribute('style');}}
                if ($tag === 'a' && $c->getAttribute('target') === '_blank') {
                    $c->setAttribute('rel', 'noopener noreferrer');}
                self::walk($c);}
            elseif ($c instanceof \DOMComment) {
                $node->removeChild($c);}}}}
