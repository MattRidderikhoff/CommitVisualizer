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

    public function updateFunctionState(FunctionState $function_state, $commit_date, $chunk) {

        $new_function_state = clone $function_state;
        $new_function_state->setCommitDate($commit_date);

        $chunk_line_offset = 0;
        $current_line_index = 0;
        while (isset($chunk['lines'][$current_line_index])) {

            $chunk_line_num = $chunk['range']['start_at'] + $current_line_index + $chunk_line_offset;
            $chunk_line = $chunk['lines'][$current_line_index];

            if ($this->isChunkLineInFunction($new_function_state, $chunk_line_num)) {

                switch($this->getChunkLineType($chunk_line)) {
                    case '+':
                        $new_function_state->addLine($chunk_line_num, $this->removeChunkLineType($chunk_line));
                        break;
                    case '-':
                        $new_function_state->removeLine($chunk_line_num);
                        $chunk_line_offset--;
                        break;
                    default: // no change
                        break;
                }

            } elseif ($this->isChunkLineBeforeFunction($new_function_state, $chunk_line_num)) {

                $new_function_state->updateRange($this->calculateRangeChange($chunk_line));
            }

            $current_line_index++;
        }

         $this->commits[] = $new_function_state;
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