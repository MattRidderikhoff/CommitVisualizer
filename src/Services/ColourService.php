<?php
/**
 * Created by PhpStorm.
 * User: matthewridderikhoff
 * Date: 2018-11-24
 * Time: 12:54 PM
 */

namespace App\Services;


class ColourService
{
    public function generateColors($files) {
        $colours = [];
        $usedColours = [];
        foreach ($files as $file) {
            $colour = $this->getNewColour($usedColours);
            $backgroundColour = $this->hexTorgba($colour, 0.4);
            $borderColour = $this->hexTorgba($colour);
            $file_name = $file->getName();
            $colours[$file_name] = [$backgroundColour, $borderColour];
            array_push($usedColours, $colour);
        }
        return $colours;
    }

    private function getRandomColour() {
        $letters = str_split('0123456789ABCDEF');
        $colour = '#';
        for ($i = 0; $i < 6; $i++) {
            $colour .= $letters[random_int(0, 15)];
        }
        return $colour;
    }

    private function hexTorgba($color, $opacity = false) {

        $default = 'rgb(0,0,0)';

        //Return default if no color provided
        if(empty($color))
            return $default;

        //Sanitize $color if "#" is provided
        if ($color[0] == '#' ) {
            $color = substr( $color, 1 );
        }

        //Check if color has 6 or 3 characters and get values
        if (strlen($color) == 6) {
            $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
        } elseif ( strlen( $color ) == 3 ) {
            $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
        } else {
            return $default;
        }

        //Convert hexadec to rgb
        $rgb =  array_map('hexdec', $hex);

        //Check if opacity is set(rgba or rgb)
        if($opacity){
            if(abs($opacity) > 1)
                $opacity = 1.0;
            $output = 'rgba('.implode(",",$rgb).','.$opacity.')';
        } else {
            $output = 'rgb('.implode(",",$rgb).')';
        }

        //Return rgb(a) color string
        return $output;
    }

    private function getNewColour($existing_colours) {
        $random_colour = $this->getRandomColour();
        while (in_array($random_colour, $existing_colours)) {
            $random_colour = $this->getRandomColour();
        }
        return $random_colour;
    }
}