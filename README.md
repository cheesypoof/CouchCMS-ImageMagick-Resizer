# CouchCMS ImageMagick Resizer

This is a replacement for the [TimThumb](http://www.binarymoon.co.uk/projects/timthumb/) image resizer script included in [CouchCMS](http://www.couchcms.com/). The excellent [ImageMagick](http://www.imagemagick.org/) engine is used (binary - not Imagick PHP Library), resulting in higher quality resized images (compared to the [GD Graphics Library](https://libgd.github.io/)).

## Requirements
Your host must have a recent version of ImageMagick installed and allow PHP's `exec` function. This script works fully with ImageMagick `6.4.8` and later. Compatibility may extend to earlier versions but has not been tested.

## Installation
1. Go to [line 16](timthumb-im.php#L16) of `timthumb-im.php` and replace `convert` with the location of your host's ImageMagick convert binary. This may be `/usr/bin/convert`, `/usr/local/bin/convert`, or something else&hellip; Contact your host for clarification.
2. Rename the existing `timthumb.php` script located in `couch/includes/` to `timthumb-gd.php`.
3. Rename `timthumb-im.php` to `timthumb.php` and place it in the `couch/includes/` directory.
