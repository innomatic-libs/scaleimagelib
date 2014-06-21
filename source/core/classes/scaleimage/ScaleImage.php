<?php

/*!
@header ScaleImageLib

@abstract With the ScaleImage class you can easyly, in two lines of code manipulate your images.

@discussion Scale logical from height, width or percentage, or just from x and y cordinates. At the same time transform from eg. png to jpg.
Author: Dennis Iversen
*/

/*!
@class ScaleImage

@abstract With the ScaleImage class you can easyly, in two lines of code manipulate your images.
*/
class ScaleImage {


    /*!
    @var image string - Placeholder for image being manipulated.
    */
    var $image;


    /*!
    @var distExtension string - imageType of the image to scale to (eg. png, jpg, gif etc.).
    */
    var $distExtension;

    /*!
    @var quality int - Quality of the scaled image (only applying to jpg).
    */
    var $quality=100;

    /*!
    @var imageType - Placeholder for imageType, eg. 'jpg' or 'png'.
    */
    var $imageType;

    /*!
    @var sendHeaders boolean - Wether or not to send headers when flushing image.
    */
    var $sendHeaders=true;

    /*!
	@function ScaleImage
    @abstract Get vaious info about image. If imageType is empty, the class will try
    to detect the 'imageType'. Currently autodetection of 'imageType' works with
    png, gif, jpeg. Set imageType if image is eg. GD or WBMP.

    @param  image string - Original image to scale (url or file).
    @param  imageType string - imageType (eg. GD, WBMP).
    */
    function ScaleImage($image='', $imageType=''){
        if (isset($image)){
            $this->setImage($image, $imageType);
        }
    }

    /*!
	@function setImage
	@abstract Open info and set info.
	@discussion Public.
    @param image string - Image or url.
    */
    function setImage($image, $imageType=''){
        $this->setImageInfo($image);
        if ($this->imageType=='unknown'){
            $this->imageType=$imageType;
            if ( empty($this->imageType) || $this->imageType == 'unknown'){
                die("Specify imageType to scale from");
            }
        }
        if ($this->imageType=='gif'){
            $this->image=imagecreatefromgif($image);
        } else if ($this->imageType=='jpg' || $this->imageType=='jpeg'){
            $this->image=imagecreatefromjpeg($image);
        } else if ($this->imageType=='png'){
            $this->image=imagecreatefrompng($image);
        } else if ($this->imageType=='gd'){
            $this->image=imagecreatefromgd($image);
        } else {
            die("Unsupported source image type: $imageType");
        }
    }


    /*!
	@function setImageInfo
	@abstract Find image size.
	@discussion Private.
    @param image string - File path.
    @result array - Image info.
    */
    function setImageInfo($image){
        $this->info=getimagesize($image, $this->info);
        if ($this->info[2]==1){
            $this->imageType='gif';
        } else if ($this->info[2]==2){
            $this->imageType='jpg';
        } else if ($this->info[2]==3){
            $this->imageType='png';
        } else {
            $this->imageType='unknown';
        }
    }

    /*!
	@function scaleMaxHeight
	@abstract Scale according to a Maximum height.
	@discussion Public.
    @param maxHeight int - Maximum height.
    @param filename string - Save image in this file (if empty output to browser).
    @param distImageType string - distImageType (scale to this imageType (jpg/png).
    */
    function scaleMaxHeight ($maxHeight, $filename='', $distImageType=''){
        if (empty($distImageType)){
            $distImageType=$this->imageType;
        }
        if ($this->info[0] <> $this->info[1]){
            $x = $maxHeight;
            $div= $this->info[0] / $maxHeight;
            $y = (int) $this->info[1] / $div;
        } else {
            $x=$y=$maxHeight;
        }
        $this->scale($x, $y, $filename, $distImageType);
    }

    /*!
	@function scaleMaxWidth
	@abstract Scale according to a Maximum width.
	@discussion Public.
    @param maxWidth int - maxWidth (maximum width).
    @param filename string - Save image in this file (if empty output to browser).
    @param distImageType string - imageType (scale to this imageType (jpg/png).
    */
    function scaleMaxWidth($maxWidth, $filename='', $distImageType=''){
        if (empty($distImageType)){
            $distImageType=$this->imageType;
        }
        if ($this->info[0] <> $this->info[1]){
            $y = $maxWidth;
            $div= $this->info[1] / $maxWidth;
            $x = $this->info[0] / $div;
        } else {
            $x=$y=$maxWidth;
        }
        $this->scale($x, $y, $filename, $distImageType);
    }

    /*!
	@function scaleXY
	@abstract Scale according to x and y cordinates.
	@discussion Public.
    @param x int - x Width.
    @param y int - y Height.
    @param filename string - save image in this file (if empty output to browser).
    @param distImageType string - imageType (scale to this imageType (jpg/png).
    */
    function scaleXY($x, $y, $filename='', $distImageType=''){
        $this->scale($x, $y, $filename, $distImageType);
    }

    /*!
	@function scaleXorY
	@abstract Scale image so the largest of x or y has gets a max of q.
	@discussion Public.
    @param max int - max Width or Height.
    @param filename string - save image in this file (if empty output to browser).
    @param distImageType string - imageType (scale to this imageType (jpg/png).
    */
    function scaleXorY($max, $filename='', $distImageType=''){
        if ($this->info[0] < $this->info[1]){
            $this->scaleMaxWidth($max, $filename, $distImageType);
        } else {
            $this->scaleMaxHeight($max, $filename, $distImageType);
        }
    }
    /*!
	@function scalePercentage
    @abstract Scale according to a percentage, eg 50.
	@discussion Public.
    @param percentage int - Percentage (percentage).
    @param filename string - Save image in this file (if empty output to browser).
    @param distImageType string - imageType (scale to this imageType (eg. jpg/png).
    */
    function scalePercentage($percentage, $filename='', $distImageType=''){
        if (empty($distImageType)){
            $distImageType=$this->imageType;
        }
        $percentage=$percentage/100;
        $x=$percentage * $this->info[0];
        $y=$percentage * $this->info[1];
        $this->scale($x, $y, $filename, $distImageType);
    }

    /*!
	@function scale
	@abstract Scale the image.
	@discussion Private.
    @param x int - width.
    @param y int - height.
    @param filename string - filename (file to put image to).
    @param distImageType string - imageType (type of image).
    */
    function scale($x, $y, $filename='', $distImageType=''){
        if ($distImageType=='gif'){
            $distImage=imagecreatetruecolor($x, $y);
            $this->copyResampled($distImage, $this->image, $x, $y);
            if (empty($filename)){
                header("Content-Type: image/gif");
                $res=@imagejpeg($distImage, '', $this->quality);
            } else {
                imagegif($distImage, $filename, $this->quality);
            }
        } else if ($distImageType=='jpg' || $distImageType=='jpeg'){
            $distImage=imagecreatetruecolor($x, $y);
            $this->copyResampled($distImage, $this->image, $x, $y);
            if (empty($filename)){
                header("Content-Type: image/jpeg");
                imagejpeg($distImage, '', $this->quality);
            } else {
                imagejpeg($distImage, $filename, $this->quality);
            }
        } else if ($distImageType=='png'){
            $distImage=imagecreatetruecolor($x, $y);
            $this->copyResampled($distImage, $this->image, $x, $y);
            if (empty($filename)){
                header("Content-Type: image/png");
                imagepng($distImage, '', $this->quality);
            } else {
                imagepng($distImage, $filename, $this->quality);
            }
        } else if ($distImageType=='gd'){
            $distImage=imagecreatetruecolor($x, $y);
            $this->copyResampled($distImage, $this->image, $x, $y);
            if (empty($filename)){
                header("Content-Type: image/gd");
                imagegd($distImage, '', $this->quality);
            } else {
                imagegd($distImage, $filename, $this->quality);
            }
        } else if ($distImageType=='wbmp'){
            $distImage=imagecreatetruecolor($x, $y);
            $this->copyResampled($distImage, $this->image, $x, $y);
            if (empty($filename)){
                header("Content-Type: image/wbmp");
                imagewbmp($distImage, '', $this->quality);
            } else {
                imagewbmp($distImage, $filename, $this->quality);
            }
        } else {
            die ("Couldn't transform image!");
        }
    }

    /*!
	@function copyResampled
	@abstract Resample the image.
	@discussion Private.
    @param distImage resource - distImage (destination image)
    @param image resource - image(sourceImage)
    @param x int
    @param y int
    */
    function copyResampled( &$distImage, $image, $x, $y){
        imagecopyresampled(
            $distImage,
            $image,
            0, 0, 0, 0,
            $x,
            $y,
            $this->info[0],
            $this->info[1]
        );
        return '';
    }
}

?>
