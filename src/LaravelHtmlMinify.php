<?php

namespace C4N\LaravelHtmlMinify;

class LaravelHtmlMinify
{
  public array $saveTags = [
    'pre',
    'code',
    'textarea',
  ];

  public function htmlMinify(string|null $html = null)
  {
    $replace = [
      // remove cdata in script tag
      // https://stackoverflow.com/a/8283600/19275735
      '~^\s*?//<!\[CDATA\[([\s\S]*)//\]\]>\s*?~m'                         => '$1',

      // remove JS line comments (simple only); do NOT remove lines containing URL (e.g. 'src="http://server.com/"')!!!
      '~//[a-zA-Z0-9 ]+$~m'                                               => '',

      // remove tabs before and after HTML tags
      '/\>[^\S ]+/s'                                                      => '>',
      '/[^\S ]+\</s'                                                      => '<',

      // shorten multiple whitespace sequences; keep new-line characters because they matter in JS!!!
      '/([\t ])+/s'                                                       => ' ',

      // remove leading and trailing spaces
      '/^([\t ])+/m'                                                      => '',
      '/([\t ])+$/m'                                                      => '',


      // remove empty lines (sequence of line-end and white-space characters)
      '/[\r\n]+([\t ]?[\r\n]+)+/s'                                        => "\n",

      // remove empty lines (between HTML tags); cannot remove just
      // any line-end characters because in inline JS they can matter!
      '/\>[\r\n\t ]+\</s'                                                 => '><',

      //remove "empty" lines containing only JS's block end character; join with next line (e.g. "}\n}\n</script>" --> "}}</script>"
      '/}[\r\n\t ]+/s'                                                    => '}',
      '/}[\r\n\t ]+,[\r\n\t ]+/s'                                         => '},',

      // remove new-line after JS's function or condition start; join with next line
      '/\)[\r\n\t ]?{[\r\n\t ]+/s'                                        => '){',
      '/,[\r\n\t ]?{[\r\n\t ]+/s'                                         => ',{',

      // remove new-line after JS's line end (only most obvious and safe cases)
      '/\),[\r\n\t ]+/s'                                                  => '),',

      // remove places where quotes connect with a closing tag to avoid errors in the next step
      '~\"/>~s'                                                           => '" />',

      // remove quotes from HTML attributes that does not contain spaces; keep quotes around URLs!
      // $1 and $4 insert first white-space character found before/after attribute
      '~([\r\n\t ])?([a-zA-Z0-9]+)="([a-zA-Z0-9_/\\-]+)"([\r\n\t ])?~s'   => '$1$2=$3$4',

      // remove any HTML comments, except MSIE conditional comments
      '/\<\!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->/s'             => '',

      '/(\n|^)(\x20+|\t)/'                                                => "\n",
      '/(\n|^)\/\/(.*?)(\n|$)/'                                           => "\n",

      // replace end of line by a space
      '/\n/'                                                              => " ",

      // remove multispace (Without \n)
      '/(\x20+|\t)/'                                                      => " ",

      // strip whitespaces between tags
      '/\>\s+\</'                                                         => "><",

      // strip whitespaces between quotation ("') and end tags
      '/(\"|\')\s+\>/'                                                    =>  "$1>",

      // strip whitespaces between = "'
      '/=\s+(\"|\')/'                                                     => "=$1",

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

    [$tags, $html] = $this->saveTags($html);

    $html = preg_replace(array_keys($replace), array_values($replace), $html);

    $html = preg_replace_callback('/(<[a-z\\-]+\\s)\\s*([^>]+>)/m', [$this, '_removeAttributeQuotes'], $html);

    // Remove extra white-space(s) between HTML attribute(s)
    $html = preg_replace_callback('#<([^\/\s<>!]+)(?:\s+([^<>]*?)\s*|\s*)(\/?)>#s', function ($matches) {
      return '<' . $matches[1] . preg_replace('#([^\s=]+)(\=([\'"]?)(.*?)\3)?(\s+|$)#s', ' $1$2', $matches[2]) . $matches[3] . '>';
    }, str_replace("\r", "", $html));

    // Minify inline CSS declaration(s)
    if (strpos($html, ' style=') !== false) {
      $html = preg_replace_callback('#<([^<]+?)\s+style=([\'"])(.*?)\2(?=[\/\s>])#s', function ($matches) {
        return '<' . $matches[1] . ' style=' . $matches[2] . minify_css($matches[3]) . $matches[2];
      }, $html);
    }
    if (strpos($html, '</style>') !== false) {
      $html = preg_replace_callback('#<style(.*?)>(.*?)</style>#is', function ($matches) {
        return '<style' . $matches[1] . '>' . minify_css($matches[2]) . '</style>';
      }, $html);
    }
    if (strpos($html, '</script>') !== false) {
      $html = preg_replace_callback('#<script(.*?)>(.*?)</script>#is', function ($matches) {
        return '<script' . $matches[1] . '>' . minify_js($matches[2]) . '</script>';
      }, $html);
    }

    $html = $this->restoreTags($html, $tags);

    return $html;
  }

  function _removeAttributeQuotes($matches)
  {
    $matches[2] = preg_replace_callback(
      '~([a-z0-9\\-])=(?<quote>[\'"])([^"\'\\s=]*)\k<quote>(\\s|>|/>)~i',
      function ($m) {
        if ($m[3] === '') {
          return $m[1] . $m[4];
        }
        if (/* 1 */
          $m[4] == '/>' ||
          /* 2 */ ($m[4] == '>' && substr($m[3], -1, 1) == '/')
        ) {
          return $m[1] . '=' . $m[3] . ' ' . $m[4];
        }
        return $m[1] . '=' . $m[3] . $m[4];
      },
      $matches[2]
    );
    return $matches[1] . $matches[2];
  }

  // https://github.com/lazev/r4initpack/blob/d38db9be957cb56a86399c4645268d15191521ad/utils/packer.php#L137
  function saveTags($input)
  {
    $saveTags = implode('|', $this->saveTags);
    preg_match_all("~\<({$saveTags})(.*?)\>(.*?)\<\/({$saveTags})\>~sim", $input, $tags);
    foreach ($tags[0] as $key => $val) {
      $input = str_replace($val, '<!~~SaveTags' . $key . '~~>', $input);
    }

    return [
      $tags,
      $input
    ];
  }

  // https://github.com/lazev/r4initpack/blob/d38db9be957cb56a86399c4645268d15191521ad/utils/packer.php#L137
  function restoreTags(&$output, $tags)
  {
    foreach ($tags[0] as $key => $val) {
      $output = str_replace('<!~~SaveTags' . $key . '~~>', $val, $output);
    }

    return $output;
  }
}
