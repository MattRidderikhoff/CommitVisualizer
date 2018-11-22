<?php
/**
 * Created by PhpStorm.
 * User: matthewridderikhoff
 * Date: 2018-11-18
 * Time: 6:45 PM
 */

namespace App\Entities;


class FileLifespan
{
    private $functions = [];
    private $file_name;
    private $file_size;
    private $creation_date;

    public function __construct($file, $commit_date)
    {
        $this->file_name = $file['filename'];
        $this->file_size = $file['additions'];
        $this->creation_date = $commit_date;
        $this->addFile($file['patch'], $commit_date);

        foreach ($this->functions as $function) {
            $function->getCurrentFunction()->setCommitBlob($file['blob_url']);
        }
    }

    // TODO: update file_size
    public function modify($file, $commit_date) {
        $lines = explode("\n", $file['patch']);

        $current_index = 0;

        if($this->file_name == 'src/Controller/BaseController.php' &&
            count($this->functions[0]->getCommits()) >= 7) {
            $i = 1;
        }


        $chunks = [];
        while ($current_index < (count($lines) - 1)) {

            $current_line = $lines[$current_index];
            if (strpos($current_line, '@@ ') !== false) {

                $results = $this->processCommitChunk($lines, $current_index, $commit_date, $file);

                $chunks[] = $results['chunk'];
                $current_index = $results['current_line_num'];
            } else {
                $current_index++;
            }
        }

        if (count($chunks) >1) {
            $a = 1;
        }

        $current_chunk_index = 0;
        $chunk_count = count($chunks);
        $chunk_range_changes = 0;
        while ($current_chunk_index < $chunk_count) {

            $current_chunk = $chunks[$current_chunk_index];
//            $current_chunk = $this->updateChunkRange($current_chunk, $chunk_range_changes);

            // TODO: is this sufficient ?
            if ($current_chunk['range']['start_at'] < $current_chunk['range']['new_start_at'])
            {
                $new_start_at = $chunks[$current_chunk_index]['range']['new_start_at'];

                $chunks[$current_chunk_index]['range']['end_at'] += $new_start_at - $chunks[$current_chunk_index]['range']['start_at'];
                $chunks[$current_chunk_index]['range']['start_at'] = $new_start_at;
            }

//            $chunk_range_changes += $current_chunk['range']['total_additions'];
            $current_chunk_index++;
        }

        if (count($chunks) >1) {
            $a = 1;
        }

        foreach($this->functions as $function_lifespan) {

            $function_state = $function_lifespan->getCurrentFunction();
            $function_lifespan->updateFunctionState($function_state, $commit_date, $chunks);
        }

        $i = 1;
    }

    private function updateChunkRange($current_chunk, $chunk_range_changes) {
        $current_chunk['range']['start_at'] += $chunk_range_changes;
        $current_chunk['range']['end_at'] += $chunk_range_changes;
        $current_chunk['range']['new_end_at'] += $chunk_range_changes;

        return $current_chunk;
    }

    private function processCommitChunk($lines, $current_line_num, $commit_date, $file) {
        $chunk_info = $lines[$current_line_num];
        $chunk_info = explode('@@', $chunk_info)[1];
        $chunk_info = explode('+', $chunk_info);

        $current_line_num++;
        $chunk['lines'] = [];
        while (strpos($lines[$current_line_num], '@@ ') === false) {

            $chunk['lines'][] = $lines[$current_line_num];
            $current_line_num++;

            if (!isset($lines[$current_line_num])) {
                break;
            }
        }

        $chunk['range'] = $this->getChunkRange($chunk_info, $file);

        return ['current_line_num' => $current_line_num, 'chunk' => $chunk];
    }

    //TODO: what if commit old > commit new
    private function getChunkRange($chunk_info, $file) {
        $chunk_remove = substr(trim($chunk_info[0]), 1);
        $chunk_remove = explode(',', $chunk_remove);

        $chunk_range['start_at'] = intval($chunk_remove[0]);
        $chunk_range['end_at'] = $chunk_range['start_at'] + $chunk_remove[1];

        $chunk_add = explode(',', trim($chunk_info[1]));
        $chunk_range['new_start_at'] = intval($chunk_add[0]);
        $chunk_range['new_end_at'] = $chunk_range['new_start_at'] + intval($chunk_add[1]);

        $chunk_range['total_additions'] = $file['additions'] - $file['deletions'];

        return $chunk_range;
    }

    private function addFile($patch, $commit_date) {
        $lines = explode("\n", $patch);

        $current_line_num = 0;
        $current_line_num++; // 1st entry isn't a line in the file, it's the diff stats

        while ($current_line_num < count($lines)) {
            $current_line = $lines[$current_line_num];

            // create function object with line count and name, AND save it. return the new current_line_num
            if (strpos($current_line,' function ') !== false) {

                if (strpos($current_line, ';') === false) { // todo: ignoring abstract/unimplemented functions for now
                    $current_line_num = $this->generateFunction($lines, $current_line_num, $commit_date);
                }
            }

            $current_line_num++;
        }
    }

    private function generateFunction($lines, $current_index, $commit_date) {
        $current_line = $lines[$current_index];
        $function_lines[] = substr($current_line, 1);

        $function_start_line = $current_index;
        $function_end_line = null;
        $left_brace_count = 0;
        if (strpos($current_line, '{') !== false) {

            if (strpos($current_line, '}') !== false) {
                $function_end_line = $current_line; // 1 line function
            } else {
                $left_brace_count++;
            }
        }

        while (!isset($function_end_line)) {
            $current_index++;
            $current_line = $lines[$current_index];
            $function_lines[] = substr($current_line, 1);

            if (strpos($current_line, '{') !== false) {
                $left_brace_count++;
            }

            if (strpos($current_line, '}') !== false) {

                if ($left_brace_count == 1) {
                    $function_end_line = $current_line;
                } else {
                    $left_brace_count--;
                }
            }
        }

        $function_name = $this->generateFunctionName(reset($function_lines));
        $this->functions[] = new FunctionLifespan(new FunctionState($function_name, $commit_date, $function_lines, $function_start_line));

        return $current_index;
    }

    private function generateFunctionName($line) {
        $function_sub_line = explode('function ', $line);
        $function_sub_line = explode('(', $function_sub_line[1]);

        return array_shift($function_sub_line);
    }

    public function getName() {
        return $this->file_name;
    }
}