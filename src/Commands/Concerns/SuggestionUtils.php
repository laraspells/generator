<?php

namespace LaraSpells\Generator\Commands\Concerns;

use LaraSpells\Generator\Schema\Table;

trait SuggestionUtils
{

    protected $suggestions = [];

    public function addSuggestion($suggestion)
    {
        $this->suggestions[] = $suggestion;
    }

    /**
     * Get suggestions
     *
     * @return array
     */
    protected function getSuggestions()
    {
        return $this->suggestions;
    }

    /**
     * Display suggestions
     *
     * @return void
     */
    protected function showSuggestions()
    {
        $suggestions = $this->getSuggestions();
        $lineLength = 80;
        if (!empty($suggestions)) {
            print(PHP_EOL);
            $this->warn(str_repeat("=", $lineLength));
            $this->warn(" WHAT NEXT?");
            $this->warn(str_repeat("=", $lineLength));
            foreach($suggestions as $i => $suggestion) {
                $n = 0;
                $lines = explode("\n", $suggestion);
                foreach($lines as $line) {
                    $_lines = $this->chunkWords($line, $lineLength);
                    foreach($_lines as $_line) {
                        if ($n === 0) {
                            $this->warn(" ".($i+1).") ".$_line);
                        } else {
                            $this->warn("    ".$_line);
                        }
                        $n++;
                    }
                }
                if ($i < count($suggestions) - 1) {
                    $this->warn(str_repeat("-", $lineLength));
                }
            }
            $this->warn(str_repeat("=", $lineLength));
        }
    }

    protected function chunkWords($text, $length)
    {
        $words = explode(" ", $text);
        $lines = [];
        $line = 0;
        foreach($words as $i => $word) {
            if (!isset($lines[$line])) {
                $lines[$line] = $word;
            } else {
                $lineText = $lines[$line];

                if (strlen($lineText.' '.$word) > $length) {
                    $line++;
                    $lines[$line] = $word;
                } else {
                    $lines[$line] .= ' '.$word;
                }
            }
        }

        return $lines;
    }

}
