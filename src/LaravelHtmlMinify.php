<?php

namespace C4N\LaravelHtmlMinify;

class LaravelHtmlMinify
{
    public function htmlMinify($html = null)
    {
        $replace = [
            //remove tabs before and after HTML tags
            '/\>[^\S ]+/s'                                                      => '>',
            '/[^\S ]+\</s'                                                      => '<',
            //shorten multiple whitespace sequences; keep new-line characters because they matter in JS!!!
            '/([\t ])+/s'                                                       => ' ',
            //remove leading and trailing spaces
            '/^([\t ])+/m'                                                      => '',
            '/([\t ])+$/m'                                                      => '',
            // remove JS line comments (simple only); do NOT remove lines containing URL (e.g. 'src="http://server.com/"')!!!
            '~//[a-zA-Z0-9 ]+$~m'                                               => '',
            //remove empty lines (sequence of line-end and white-space characters)
            '/[\r\n]+([\t ]?[\r\n]+)+/s'                                        => "\n",
            //remove empty lines (between HTML tags); cannot remove just any line-end characters because in inline JS they can matter!
            '/\>[\r\n\t ]+\</s'                                                 => '><',
            //remove "empty" lines containing only JS's block end character; join with next line (e.g. "}\n}\n</script>" --> "}}</script>"
            '/}[\r\n\t ]+/s'                                                    => '}',
            '/}[\r\n\t ]+,[\r\n\t ]+/s'                                         => '},',
            //remove new-line after JS's function or condition start; join with next line
            '/\)[\r\n\t ]?{[\r\n\t ]+/s'                                        => '){',
            '/,[\r\n\t ]?{[\r\n\t ]+/s'                                         => ',{',
            //remove new-line after JS's line end (only most obvious and safe cases)
            '/\),[\r\n\t ]+/s'                                                  => '),',
            //remove quotes from HTML attributes that does not contain spaces; keep quotes around URLs!
            //'~([\r\n\t ])?([a-zA-Z0-9]+)=\"([a-zA-Z0-9_\\-]+)\"([\r\n\t ])?~s'  => '$1$2=$3$4',

            '/(\n|^)(\x20+|\t)/' => "\n",
            '/(\n|^)\/\/(.*?)(\n|$)/' => "\n",
            '/\n/' => " ",
            '/\<\!--.*?-->/' => "",
            # Delete multispace (Without \n)
            '/(\x20+|\t)/' => " ",
            # strip whitespaces between tags
            '/\>\s+\</' => "><",
            # strip whitespaces between quotation ("') and end tags
            '/(\"|\')\s+\>/' =>  "$1>",
            # strip whitespaces between = "'
            '/=\s+(\"|\')/' => "=$1",
            '%		# Collapse ws everywhere but in blacklisted elements.
            (?>             # Match all whitespans other than single space.
              [^\S ]\s*     # Either one [\t\r\n\f\v] and zero or more ws,
            | \s{2,}        # or two or more consecutive-any-whitespace.
            ) 				# Note: The remaining regex consumes no text at all...
            (?=             # Ensure we are not in a blacklist tag.
              (?:           # Begin (unnecessary) group.
                (?:         # Zero or more of...
                  [^<]++    # Either one or more non-"<"
                | <         # or a < starting a non-blacklist tag.
                  (?!/?(?:textarea|pre)\b)
                )*+         # (This could be "unroll-the-loop"ified.)
              )             # End (unnecessary) group.
              (?:           # Begin alternation group.
                <           # Either a blacklist start tag.
                (?>textarea|pre)\b
              | \z          # or end of file.
              )             # End alternation group.
            )  				# If we made it here, we are not in a blacklist tag.
            %ix' => " "
        ];
        $html = preg_replace(array_keys($replace), array_values($replace), $html);

        
        $html = preg_replace_callback('/(<[a-z\\-]+\\s)\\s*([^>]+>)/m', [$this, '_removeAttributeQuotes'], $html);

        return $html;
    }

    function _removeAttributeQuotes($m) {	
        $m[2] = preg_replace_callback('~([a-z0-9\\-])=(?<quote>[\'"])([^"\'\\s=]*)\k<quote>(\\s|>|/>)~i', [$this, '_removeAttributeQuotesCallback'], $m[2]);	
        return $m[1].$m[2];
    }
            
    function _removeAttributeQuotesCallback($m) {
        if ($m[3] === '') {
            return $m[1].$m[4];
        }
        if (/* 1 */ $m[4] == '/>' ||
            /* 2 */ ($m[4] == '>' && substr($m[3], -1, 1) == '/')) {
            return $m[1].'='.$m[3].' '.$m[4];
        }
        return $m[1].'='.$m[3].$m[4];
    }
}
