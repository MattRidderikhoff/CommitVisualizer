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

    public function getPreviousCommit() {
        return $this->commits[count($this->commits) - 2];
    }

    public function getCommits() {
        return $this->commits;
    }

    public function replaceFunctionState($new_function_state) {
        array_pop($this->commits);
        $this->commits[] = $new_function_state;
    }

    public function removeUnnecessaryCommits() {
        $commits = [];

        $current_commit = $this->commits[0];
        $commits[] = $current_commit;
        $current_index = 1;

        while ($current_index < count($this->commits)) {
            $prev_commit = $current_commit;
            $current_commit = $this->commits[$current_index];

            $same_lines = $this->sameLines($prev_commit, $current_commit);
            $same_range = $this->sameRange($prev_commit, $current_commit);

            if (!$same_lines && !$same_range) {
                $commits[] = $current_commit;
            }

            $current_index++;
        }

        $this->commits = $commits;
    }

    private function sameLines(FunctionState $prev_commit, FunctionState $current_commit) {
        return $prev_commit->getLines() == $current_commit->getLines();
    }

    private function sameRange(FunctionState $prev_commit, FunctionState $current_commit) {
        return $prev_commit->getRange() == $current_commit->getRange();
    }

    public function updateFunctionState(FunctionState $function_state, $commit_date, $chunks) {

        $new_function_state = clone $function_state;
        $new_function_state->setCommitDate($commit_date);

        foreach ($chunks as $chunk) {
            $new_function_state = $this->updateFunctionByChunk($new_function_state, $chunk);
        }

        $this->commits[] = $new_function_state;
     }

    private function updateFunctionByChunk(FunctionState $function_state, $chunk)
    {

        $chunk_line_offset = 0;
        $current_line_index = 0;
        while (isset($chunk['lines'][$current_line_index])) {

            $chunk_line_num = $chunk['range']['start_at'] + $current_line_index + $chunk_line_offset;
            $chunk_line = $chunk['lines'][$current_line_index];

            if ($this->isChunkLineInFunction($function_state, $chunk_line_num)) {

                switch ($this->getChunkLineType($chunk_line)) {
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