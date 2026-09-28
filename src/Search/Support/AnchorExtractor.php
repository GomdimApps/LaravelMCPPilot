<?php

namespace GomdimApps\LaravelMCPPilot\Search\Support;

/**
 * Semantic-anchor + balanced-bracket text extraction, language-agnostic (works on PHP, Vue,
 * or TypeScript source alike). No AST, no execution — just a recursive PCRE pattern.
 */
class AnchorExtractor
{
    /**
     * Finds the anchor, then the matching close for the open delimiter that follows it, via a
     * recursive PCRE pattern (?1) — no manual bracket-counting loop needed. $afterPattern must
     * reach all the way to the opener itself (e.g. "foo\s*" for an immediate "foo(", or
     * "function bar\(\)[^{]*\{.*?return\s+" to bridge boilerplate before a return). A loose,
     * unbounded gap here would risk matching an unrelated pair from a different, earlier
     * occurrence of the anchor word (e.g. inside an unrelated import statement).
     */
    public function balanced(string $content, string $afterPattern, string $open, string $close): ?string
    {
        $o = preg_quote($open, '/');
        $c = preg_quote($close, '/');
        $pattern = '/'.$afterPattern.'('.$o.'(?:[^'.$o.$c.']++|(?1))*+'.$c.')/s';

        return preg_match($pattern, $content, $matches) ? trim(substr($matches[1], 1, -1)) : null;
    }

    /** Top-level `key`/`key:` names inside a captured object/array body — a dense field list, not a full dump. */
    public function fieldNames(string $body): array
    {
        preg_match_all('/^\s*[\'"]?([A-Za-z_$][\w$]*)[\'"]?\s*[:=]/m', $body, $matches);

        return array_values(array_unique($matches[1]));
    }

    /** Plain quoted-string list items, e.g. `$fillable = ['user_id', 'subject', ...]`. */
    public function quotedItems(string $body): array
    {
        preg_match_all('/[\'"]([^\'"]+)[\'"]/', $body, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * Splits an argument-list body on top-level commas only — one nested inside (), [], {}, or a
     * quoted string doesn't count. Used to pull the first argument out of a call like
     * `belongsTo(User::class, 'author_id')` without a comma inside a later argument breaking it.
     */
    public function topLevelSegments(string $body): array
    {
        $segments = [];
        $segment = '';
        $depth = 0;
        $quote = null;

        foreach (str_split($body) as $char) {
            $segment .= $char;

            if ($quote !== null) {
                $quote = $char === $quote ? null : $quote;
            } elseif ($char === '\'' || $char === '"') {
                $quote = $char;
            } elseif (str_contains('([{', $char)) {
                $depth++;
            } elseif (str_contains(')]}', $char)) {
                $depth--;
            } elseif ($char === ',' && $depth === 0) {
                $segments[] = trim(substr($segment, 0, -1));
                $segment = '';
            }
        }

        $segments[] = trim($segment);

        return array_values(array_filter($segments, fn (string $segment) => $segment !== ''));
    }
}
