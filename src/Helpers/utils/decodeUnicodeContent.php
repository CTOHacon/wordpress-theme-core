<?php
/**
 * Decode JSON unicode escapes (\uXXXX) to actual characters
 * Useful when copying content from Gutenberg's JSON code editor
 * 
 * @param string $content Content with unicode escapes
 * @return string Decoded content
 */
function decode_unicode_content(string $content): string
{
    return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($matches) {
        return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UCS-2BE');
    }, $content);
}
