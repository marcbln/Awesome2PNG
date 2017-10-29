<?php

class Awesome2PNG
{

    private $im;

    public function __construct()
    {
    }


    /**
    /*
     * Generate PNG from Font-Awesome TTF
     * ----------------------------------
     * The constructor takes the Wanted PNG height in pixels and brute-forces
     * the correct (or best effort attempt) height in points to render the font.
     * This may be a slow process, but the result is expected to be cached in a
     * file. So, pretty pictures that are correctly sized, instead of a speedy
     * result.
     *
     * The contructor also takes in the unicode hex value of the wanted
     * character and a hex colour code. Also, a padding array with pixel counts:
     *     array(top, bottom, left, right)
     *
     * The result is an awesome transparent PNG glyph with the character of
     * choice rendered in the colour expected with the padding of choice
     * applied. Padding can be positive or negative. A negative value will cause
     * the glyph to crop.
     *
     * Requires GD version 2, FreeType, and a FontAwesome TTF file
     *
     * Copyright Stephen Perelson
     *
     *
     * @param $charName
     * @param int $pixelshigh
     * @param string $color
     * @param int $alpha
     * @param array $padding
     */
    public function render($charName, $pixelshigh = 30, $color = '000000', $alpha = 0, $padding = [10, 10, 10, 10])
    {
        $unicodeChar = $this->name2code($charName);

        // Variables for brute-forcing the correct point height
        $ratio = 96 / 72;
        $ratioadd = 0.0001;
        $heightalright = false;
        $count = 0;
        $maxcount = 20000;

        // Set the enviroment variable for GD
        putenv('GDFONTPATH=' . realpath('.'));
        $font = 'fontawesome-webfont';

        $text = json_decode('"' . $unicodeChar . '"');

        // Brute-force point height
        while (!$heightalright && $count < $maxcount) {
            $x = $pixelshigh / $ratio;
            $count++;
            $bounds = imagettfbbox($x, 0, $font, $text);
            $height = abs($bounds[7] - abs($bounds[1]));

            if ($height == $pixelshigh) {
                $heightalright = true;
            } else {
                if ($height < $pixelshigh) {
                    $ratio -= $ratioadd;
                } else {
                    $ratio += $ratioadd;
                }
            }
        }
        $width = abs($bounds[4]) + abs($bounds[0]);
        // Create the image
        $this->im = imagecreatetruecolor($width + $padding[2] + $padding[3], $pixelshigh + $padding[0] + $padding[1]);
        imagesavealpha($this->im, true);
        $trans = imagecolorallocatealpha($this->im, 0, 0, 0, 127);
        imagefill($this->im, 0, 0, $trans);
        imagealphablending($this->im, true);

        $fontcolor = self::makecolor($color, $alpha);

        // Add the text
        imagettftext($this->im, $x, 0, 1 + $padding[2], $height - abs($bounds[1]) - 1 + $padding[0], $fontcolor, $font, $text);
    }


    /**
     * @param string $name eg "twitter"
     * @return string $code
     */
    private function name2code($name)
    {
        $vars = file_get_contents(__DIR__ . '/variables.less');
        if (!preg_match('#@fa-var-' . $name . '\s*:\s*"\\\(.*)"#', $vars, $gr)) {
            die("$name not found");
        }
        $code = '&#x' . $gr[1] . ';';

        return $code;
    }

    /**
     * @param $pathDest
     */
    public function save($pathDest)
    {
        imagesavealpha($this->im, true);
        imagepng($this->im, $pathDest);
    }

    /**
     * @param $hexcolor
     * @param $alpha
     * @return int
     */
    static private function makecolor($hexcolor, $alpha)
    {
        return $alpha << 24 | hexdec($hexcolor);
    }

    /**
     *
     */
    public function toBrowser()
    {
        header('Content-type: image/png');
        imagepng($this->im);
    }
}


// ---- test
// for codes see here: https://github.com/FortAwesome/Font-Awesome/blob/master/less/variables.less

$faRenderer = new Awesome2PNG();
$faRenderer->render('heart', 400, 'ff0000');
$faRenderer->toBrowser();
//$faRenderer->save(__DIR__ . '/heart.png');

