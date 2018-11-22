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

        $current_line_index = 0;
        while (isset($chunk['lines'][$current_line_index])) {

            $chunk_line_num = $chunk['range']['start_at'] + $current_line_index;
            if ($this->isChunkLineInFunction($new_function_state, $chunk_line_num)) {

                $this->updateFunctionLines($new_function_state, $chunk['lines'][$current_line_index], $chunk_line_num);

            } elseif ($this->isChunkLineBeforeFunction($new_function_state, $chunk_line_num)) {

//                $this->updateFunctionLineNums($function_state, $chunk_line_num);
            }

            $current_line_index++;
        }

         $this->commits[] = $new_function_state;
    }

    // TODO: this assumes that all changes in the chunk are happening WITHIN this function. They may be happening before or after
    private function updateFunctionLines(FunctionState $function_state, $chunk_line, $chunk_line_num) {
        switch($this->getChunkLineType($chunk_line)) {
            case '+':
                $function_state->addLine($chunk_line_num, $this->removeChunkLineType($chunk_line));
                break;
            case '-':
                $function_state->removeLine($chunk_line_num);
                break;
            default: // no change
                break;
        }
    }

    private function getChunkLineType($line) {
        return substr($line, 0, 1);
    }

    private function removeChunkLineType($line) {
        return substr($line, 1);
    }

    private function isChunkLineInFunction(FunctionState $function_state, $line_num) {
        return ($line_num >= $function_state->getStartLineNum() && $line_num <= $function_state->getEndLineNum());
    }

    private function isChunkLineBeforeFunction(FunctionState $function_state, $line_num) {
        return $function_state->getStartLineNum() < $line_num;
    }
}