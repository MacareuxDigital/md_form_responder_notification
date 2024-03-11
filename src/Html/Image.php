<?php

namespace Macareux\Package\FormResponderNotification\Html;

use Concrete\Core\Entity\File\File;
use HtmlObject\Image as HtmlObjectImage;

/**
 * Custom version of \Concrete\Core\Html\Image for usage in the email content.
 */
class Image
{
    protected $tag;

    public function __construct(File $f = null)
    {
        if (!is_object($f)) {
            return false;
        }

        // Always use full url for images in emails
        $path = $f->getURL();

        // Return a simple img element.
        $this->tag = HtmlObjectImage::create($path);
        $this->tag->width((string)$f->getAttribute('width'));
        $this->tag->height((string)$f->getAttribute('height'));
    }

    /**
     * Returns an object that represents the HTML tag.
     *
     * @see https://github.com/Anahkiasen/html-object
     *
     * @return \HtmlObject\Image
     */
    public function getTag()
    {
        return $this->tag;
    }
}