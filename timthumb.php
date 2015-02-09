<?php
/*
    https://github.com/cheesypoof/CouchCMS-ImageMagick-Resizer

    Modified from the original source of TimThumb script created by Tim McDaniels and Darren Hoyt.
    Original license reproduced below.

    TimThumb script created by Tim McDaniels and Darren Hoyt with tweaks by Ben Gillbanks
    http://code.google.com/p/timthumb/

    MIT License: http://www.opensource.org/licenses/mit-license.php
*/

if (!defined('K_COUCH_DIR')) die(); // cannot be loaded directly

define('K_IMAGEMAGICK_PATH', 'convert'); // path to ImageMagick's convert binary

function k_resize_image($src, $dest = 0, $new_width = 0, $new_height = 0, $zoom_crop = 1, $enforce_max = 0, $quality = 80, $crop_position = 'middle', $check_thumb_exists = 0) {

    global $FUNCS;

    // check to see if GD functions exist
    if (!function_exists('imagecreatetruecolor')) {
        return displayError('GD Library Error: imagecreatetruecolor does not exist - please contact your webhost and ask them to install the GD library');
    }

    // check to see if the exec function exists
    if (!function_exists('exec')) {
        return displayError('Error: exec does not exist - please contact your webhost');
    }

    if (trim($src) == '') {
        return displayError('Source image not set');
    }

    // get mime type of src
    $mime_type = mime_type($src);

    ini_set('memory_limit', '128M');

    // make sure that the src is gif/jpg/png
    if (!valid_src_mime_type($mime_type)) {
        return displayError('Invalid src mime type: ' . $mime_type);
    }

    if (strlen($src) && file_exists($src)) {

        // open the existing image
        $image = open_image($mime_type, $src);
        if ($image === false) {
            return displayError('Unable to open image : ' . $src);
        }

        // get original width and height
        $width = imagesx($image);
        $height = imagesy($image);

        imageDestroy($image);

        // generate new w/h if not provided
        if ($new_width && !$new_height) {
            $new_height = $height * ($new_width / $width);
        } else if ($new_height && !$new_width) {
            $new_width = $width * ($new_height / $height);
        } else if (!$new_width && !$new_height) {
            $new_width = $width;
            $new_height = $height;
        }

        // if new dimensions cannot exceed certain values
        if ($enforce_max) {
            // the supplied width and height were actually the max permissible values
            $max_width = $new_width;
            $max_height = $new_height;

            // make the new values the same as that of the source image
            $new_width = $width;
            $new_height = $height;

            // if new dimensions already within bounds (and this not a thumbnail that we are creating), return
            if (($src==$dest) && ($new_width <= $max_width) && ($new_height <= $max_height)) {
                return;
            }

            if ($new_width > $max_width) {
                if (!$zoom_crop) {
                    $ratio = (real) ($max_width / $new_width);
                    $new_width = (int) ($new_width * $ratio);
                    $new_height = (int) ($new_height * $ratio);
                } else {
                    $new_width = $max_width;
                }
            }

            // if new height still overshoots maximum value
            if ($new_height > $max_height) {
                if (!$zoom_crop) {
                    $ratio = (real) ($max_height / $new_height);
                    $new_width = (int) ($new_width * $ratio);
                    $new_height = (int) ($new_height * $ratio);
                } else {
                    $new_height = $max_height;
                }
            }
        }

        // create filename if not provided one (happens only for thumbnails)
        if (!$dest) {
            $path_parts = $FUNCS->pathinfo($src);
            $thumb_name = $path_parts['filename'] . '-' . round($new_width) . 'x' . round($new_height) . '.' . $path_parts['extension'];
            $thumbnail = $path_parts['dirname'] . '/' . $thumb_name;

            if ($check_thumb_exists && file_exists($thumbnail)) {
                return $thumb_name;
            }
        }

        if ($zoom_crop) {
            $cmp_x = $width / $new_width;
            $cmp_y = $height / $new_height;

            // if new dimensions equal to the original (and this not a thumbnail that we are creating), return
            if ($src == $dest && $cmp_x == 1 && $cmp_y == 1) {
                return;
            }

            switch ($crop_position) {
                case 'top_left':
                    $gravity = 'northwest';
                    break;
                case 'top_center':
                    $gravity = 'north';
                    break;
                case 'top_right':
                    $gravity = 'northeast';
                    break;
                case 'middle_left':
                    $gravity = 'west';
                    break;
                case 'middle':
                    $gravity = 'center';
                    break;
                case 'middle_right':
                    $gravity = 'east';
                    break;
                case 'bottom_left':
                    $gravity = 'southwest';
                    break;
                case 'bottom_center':
                    $gravity = 'south';
                    break;
                case 'bottom_right':
                    $gravity = 'southeast';
                    break;
            }
        }

        if (!$dest) {
            $dest = $thumbnail;
        }

        if (@touch($dest)) {
            // give 666 permissions so that the developer can overwrite web server user
            @chmod($dest, 0666);

            $format = ($mime_type == 'image/jpeg') ? 'jpg' : (($mime_type = 'image/png') ? 'png' : 'gif');

            $transparent = ($format == 'png' || $format == 'gif') ? ' -background none' : '';

            $resize = ($cmp_x > $cmp_y) ? "x{$new_height}" : $new_width;

            $crop = ($zoom_crop) ? "{$resize} -gravity {$gravity} -extent {$new_width}x{$new_height}!" : "{$new_width}x{$new_height}!";

            $jpg = ($format == 'jpg') ? " -compress JPEG -define jpeg:optimize-coding=true -quality {$quality}%" : '';

            // limits in bytes: 160 MB and 128 MB
            exec(K_IMAGEMAGICK_PATH . " -limit memory 167772160 -limit map 134217728 -format {$format}{$transparent} {$src} -thumbnail {$crop}{$jpg} {$dest}");
        }

        return $thumb_name;
    } else {
        if (strlen($src)) {
            return displayError('image ' . $src . ' not found');
        } else {
            return displayError('no source specified');
        }
    }

    return;

}

/**
 * save image
 */
function save_image($mime_type, $image_resized, $file, $quality = 80) {

    if (@touch($file)) {
        // give 666 permissions so that the developer can overwrite web server user
        @chmod($file, 0666);

        switch ($mime_type) {
            case 'image/jpeg':
                imagejpeg($image_resized, $file, $quality);
                break;
            default:
                $quality = floor($quality * 0.09);
                imagepng($image_resized, $file, $quality);
        }
    }

}

/**
 * open image
 */
function open_image($mime_type, $src) {

    $mime_type = strtolower($mime_type);

    if (stristr($mime_type, 'gif')) {
        $image = @imagecreatefromgif($src);
    } else if (stristr($mime_type, 'jpeg')) {
        @ini_set('gd.jpeg_ignore_warning', 1);

        $image = @imagecreatefromjpeg($src);
    } else if (stristr($mime_type, 'png')) {
        $image = @imagecreatefrompng($src);
    }

    return $image;

}

/**
 * determine the file mime type
 */
function mime_type($file) {

    if (stristr(PHP_OS, 'WIN')) {
        $os = 'WIN';
    } else {
        $os = PHP_OS;
    }

    $mime_type = '';

    if (function_exists('mime_content_type')) {
        $mime_type = @mime_content_type($file);
    }

    // use PECL fileinfo to determine mime type
    if (!valid_src_mime_type($mime_type)) {
        if (function_exists('finfo_open')) {
            $finfo = @finfo_open(FILEINFO_MIME);
            if ($finfo != '') {
                $mime_type = finfo_file($finfo, $file);
                finfo_close($finfo);
            }
        }
    }

    // try to determine mime type by using unix file command
    // this should not be executed on windows
    if (!valid_src_mime_type($mime_type) && $os != 'WIN') {
        if (preg_match("/FREEBSD|LINUX/", $os)) {
            $mime_type = trim(@shell_exec('file -bi ' . escapeshellarg($file)));
        }
    }

    // use file's extension to determine mime type
    if (!valid_src_mime_type($mime_type)) {
        // set defaults
        $mime_type = 'image/png';
        // file details
        $fileDetails = pathinfo($file);
        $ext = strtolower($fileDetails['extension']);
        // mime types
        $types = array(
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        );

        if (strlen($ext) && strlen($types[$ext])) {
            $mime_type = $types[$ext];
        }
    }

    return $mime_type;

}

/**
 * check if the mime type is valid
 */
function valid_src_mime_type($mime_type) {

    if (preg_match("/jpg|jpeg|gif|png/i", $mime_type)) {
        return true;
    }

    return false;

}

/**
 * check if the url is valid
 */
function valid_extension($ext) {

    if (preg_match("/jpg|jpeg|png|gif/i", $ext)) {
        return true;
    } else {
        return false;
    }

}

/**
 * generic error message
 */
function displayError($errorString = '') {

    global $FUNCS;
    return $FUNCS->raise_error($errorString);

}
