# [CouchCMS](http://www.couchcms.com/) ImageMagick Resizer

This is a replacement for the default [TimThumb](http://www.binarymoon.co.uk/projects/timthumb/) image resizer script. The excellent [ImageMagick](http://www.imagemagick.org/) engine is used (binary - not Imagick PHP Library), resulting in higher quality resized images (compared to the [GD Graphics Library](https://libgd.github.io/)).

## Requirements
Your host must have a recent version of ImageMagick installed and allow PHP's `exec` function. This script works fully with ImageMagick `6.4.8` and later. Compatibility may extend to earlier versions but has not been tested.

## Installation
1. Rename the existing `timthumb.php` script located in `couch/includes` to `timthumb-gd.php`.
2. Go to [line 16](timthumb.php#L16) of the new `timthumb.php` and replace `convert` with the location of your host's ImageMagick convert binary. This may be `/usr/bin/convert`, `/usr/local/bin/convert`, or something else&hellip; Contact your host for clarification.
3. Place `timthumb.php` in the `couch/includes` directory.
