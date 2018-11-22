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

        if ($this->functionInChunkRange($new_function_state, $chunk['range'])) {

            $this->updateFunctionLines($new_function_state, $chunk);
        }

//        } elseif () {
//            // if there are removals/additions BEFORE this function, update function lines
//        }

        $this->commits[] = $new_function_state;
    }


    // TODO: this assumes that all changes in the chunk are happening WITHIN this function. They may be happening before or after
    private function updateFunctionLines($function_state, $chunk) {
        $current_line_num = 1;
        foreach ($chunk['lines'] as $chunk_line) {

            switch($this->getChunkLineType($chunk_line)) {
                case '+':
                    $this->addFunctionLine($function_state, $chunk['range']['start_at'], $chunk_line, $current_line_num);
                    break;
                case '-':
                    $this->removeFunctionLine($function_state, $chunk['range']['start_at'], $current_line_num);
                    break;
                default: // no change
                    break;
            }
            $current_line_num++;
        }
    }

    private function addFunctionLine(FunctionState $function_state, $chunk_line_start, $line, $line_num) {
        $line_index = $chunk_line_start + $line_num - $function_state->getStartLineNum() - 1; // -1 to account for [0];
        $function_state->addLine($this->removeChunkLineType($line), $line_index);
    }

    private function removeFunctionLine(FunctionState $function_state, $chunk_line_start, $line_num) {

        $line_index = $chunk_line_start + $line_num - $function_state->getStartLineNum() - 1; // -1 to account for [0]
        $function_state->removeLine($line_index);
    }

    private function functionInChunkRange(FunctionState $function_state, $chunk_range) {
        return (($function_state->getStartLineNum() <= $chunk_range['end_at']) && ($function_state->getEndLineNum() >= $chunk_range['start_at']));
    }

    private function getChunkLineType($line) {
        return substr($line, 0, 1);
    }

    private function removeChunkLineType($line) {
        return substr($line, 1);
    }
}