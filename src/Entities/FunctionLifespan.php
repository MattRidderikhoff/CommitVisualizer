<?php
/**
 * Created by PhpStorm.
 * User: matthewridderikhoff
 * Date: 2018-11-19
 * Time: 10:35 AM
 */

namespace App\Entities;


class FunctionLifespan
{
    private $commits;

    public function __construct(FunctionState $first_commit)
    {
        $this->commits[] = $first_commit;
    }

    public function getCurrentName() {
        return end($this->commits)->getName();
    }

    public function getCurrentFunction() {
        return end($this->commits);
    }

    public function getCommits() {
        return $this->commits;
    }

    public function replaceFunctionState($new_function_state) {
        array_pop($this->commits);
        $this->commits[] = $new_function_state;
    }

    public function updateFunctionState(FunctionState $function_state, $commit_date, $chunks) {

        $new_function_state = clone $function_state;
        $new_function_state->setCommitDate($commit_date);

        // need to include "total_additions" from prev chunks
        foreach ($chunks as $chunk) {
            $new_function_state = $this->updateFunctionByChunk($new_function_state, $chunk);
        }

        $this->commits[] = $new_function_state;
     }

    private function updateFunctionByChunk(FunctionState $function_state, $chunk) {

        $chunk_line_offset = 0;
        $current_line_index = 0;
        while (isset($chunk['lines'][$current_line_index])) {

            $chunk_line_num = $chunk['range']['start_at'] + $current_line_index + $chunk_line_offset;
            $chunk_line = $chunk['lines'][$current_line_index];

            if ($this->isChunkLineInFunction($function_state, $chunk_line_num)) {

                // if line # = function->line_start_num && function->name can be found in the line AFTER this continuous additions... then append those nums to the range
                if ($chunk_line_num == $function_state->getStartLineNum() && $this->nextNonAdditionLineNumContainFunctionName($function_state, $chunk, $current_line_index)) {
                    $i = 1;
                }

                switch($this->getChunkLineType($chunk_line)) {
                    case '+':
                        $function_state->addLine($chunk_line_num, $this->removeChunkLineType($chunk_line));
                        break;
                    case '-':
                        $function_state->removeLine($chunk_line_num);
                        $chunk_line_offset--;
                        break;
                    default: // no change
                        break;
                }

            } elseif ($this->isChunkLineBeforeFunction($function_state, $chunk_line_num)) {

                $line_change = $this->calculateRangeChange($chunk_line);
                $function_state->updateRange($line_change);

                if ($line_change == (-1)) {
                    $chunk_line_offset--;
                }
            }

            $current_line_index++;
        }

        return $function_state;
    }

    private function nextNonAdditionLineNumContainFunctionName(FunctionState $new_function_state, $chunk, $current_index) {
        $current_line = $chunk['lines'][$current_index];

        if ($this->getChunkLineType($current_line) != '+') {
            return false;
        }

        $type = $this->getChunkLineType($current_line);
        $function_name = $new_function_state->getName();

        while ($this->getChunkLineType($current_line) == '+') {
            $current_index++;
            $current_line = $chunk['lines'][$current_index];
        }

        $a = 1;

        return false;
    }

    private function calculateRangeChange($chunk_line) {
        switch($this->getChunkLineType($chunk_line)) {
            case '+':
                return 1;
                break;
            case '-':
                return -1;
                break;
            default: // no change
                return 0;
                break;
        }
    }

    private function isChunkLineInFunction(FunctionState $function_state, $line_num) {
        return ($line_num >= $function_state->getStartLineNum() && $line_num <= $function_state->getEndLineNum());
    }

    private function isChunkLineBeforeFunction(FunctionState $function_state, $line_num) {
        return $line_num < $function_state->getStartLineNum();
    }

    private function getChunkLineType($line) {
        return substr($line, 0, 1);
    }

    private function removeChunkLineType($line) {
        return substr($line, 1);
    }
}