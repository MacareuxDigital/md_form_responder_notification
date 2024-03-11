<?php

namespace Macareux\Package\FormResponderNotification\Editor;

use Concrete\Core\Editor\Snippet;
use Concrete\Core\Html\Object\Picture;
use Concrete\Core\Page\Page;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Macareux\Package\FormResponderNotification\Html\Image;
use Sunra\PhpSimple\HtmlDomParser;

class LinkAbstractor extends \Concrete\Core\Editor\LinkAbstractor
{
    /**
     * Takes a chunk of content containing full urls
     * and converts them to abstract link references.
     */
    private static $blackListImgAttributes = ['src', 'fid', 'data-verified', 'data-save-url'];

    /**
     * Overrides the core method to add support for the custom image class.
     */
    public static function translateFrom($text)
    {
        if (($text = (string) $text) === '') {
            return $text;
        }
        $app = Application::getFacadeApplication();
        $resolver = $app->make(ResolverManagerInterface::class);

        $text = preg_replace(
            [
                '/{CCM:BASE_URL}/i',
            ],
            [
                Application::getApplicationURL(),
            ],
            $text
        );

        // now we add in support for the links
        $text = static::replacePlaceholder(
            $text,
            '{CCM:CID_([0-9]+)}',
            function ($cID) use ($resolver) {
                if ($cID > 0) {
                    $c = Page::getByID($cID, 'ACTIVE');
                    if ($c->isActive()) {
                        return $resolver->resolve([$c]);
                    }
                }

                return '';
            }
        );

        // now we add in support for the files that we view inline
        $dom = new HtmlDomParser();
        $r = $dom->str_get_html($text, true, true, DEFAULT_TARGET_CHARSET, false);
        if (is_object($r)) {
            foreach ($r->find('concrete-picture') as $picture) {
                $fID = $picture->fid;
                if (uuid_is_valid($fID)) {
                    $fo = \Concrete\Core\File\File::getByUUID($fID);
                } else {
                    $fo = \Concrete\Core\File\File::getByID($fID);
                }
                if ($fo !== null) {
                    $style = (string) $picture->style;
                    // move width px to width attribute and height px to height attribute
                    $widthPattern = '/(?:^width|[^-]width):\\s([0-9]+)px;?/i';
                    if (preg_match($widthPattern, $style, $matches)) {
                        $style = preg_replace($widthPattern, '', $style);
                        $picture->width = $matches[1];
                    }
                    $heightPattern = '/(?:^height|[^-]height):\\s([0-9]+)px;?/i';
                    if (preg_match($heightPattern, $style, $matches)) {
                        $style = preg_replace($heightPattern, '', $style);
                        $picture->height = $matches[1];
                    }
                    if ($style === '') {
                        unset($picture->style);
                    } else {
                        $picture->style = $style;
                    }
                    $image = new Image($fo); // Changed from \Concrete\Core\Html\Image to \Macareux\Package\FormResponderNotification\Html\Image
                    $tag = $image->getTag();

                    foreach ($picture->attr as $attr => $val) {
                        $attr = (string) $attr;
                        if (!in_array($attr, self::$blackListImgAttributes)) {
                            //Apply attributes to child img, if using picture tag.
                            if ($tag instanceof Picture) {
                                foreach ($tag->getChildren() as $child) {
                                    if ($child instanceof \HtmlObject\Image) {
                                        $child->{$attr}($val);
                                    }
                                }
                            } elseif (is_callable([$tag, $attr])) {
                                $tag->{$attr}($val);
                            } else {
                                $tag->setAttribute($attr, $val);
                            }
                        }
                    }

                    if (!in_array('alt', array_keys($picture->attr))) {
                        if ($tag instanceof Picture) {
                            foreach ($tag->getChildren() as $child) {
                                if ($child instanceof \HtmlObject\Image) {
                                    $child->alt('');
                                }
                            }
                        } else {
                            $tag->alt('');
                        }
                    }

                    $picture->outertext = (string) $tag;
                }
            }

            $text = (string) $r->restore_noise($r);
        }

        // now we add in support for the links
        $text = static::replacePlaceholder(
            $text,
            '{CCM:FID_([a-f0-9-]{36}|[0-9]+)}',
            function ($fID) {
                if ($fID) {
                    if (uuid_is_valid($fID)) {
                        $f = \Concrete\Core\File\File::getByUUID($fID);
                    } else {
                        $f = \Concrete\Core\File\File::getByID($fID);
                    }
                    if ($f !== null) {
                        return $f->getURL();
                    }
                }

                return '';
            }
        );

        // now files we download
        $currentPage = null;
        $text = static::replacePlaceholder(
            $text,
            '{CCM:FID_DL_([a-f0-9-]{36}|[0-9]+)}',
            function ($fID) use ($resolver, &$currentPage) {
                if ($fID) {
                    $args = ['/download_file', 'view', $fID];
                    if ($currentPage === null) {
                        $currentPage = Page::getCurrentPage();
                        if (!$currentPage || $currentPage->isError()) {
                            $currentPage = false;
                        }
                    }
                    if ($currentPage !== false) {
                        $args[] = $currentPage->getCollectionID();
                    }
                    return $resolver->resolve($args);
                }

                return '';
            }
        );

        // snippets
        if (strrpos($text, 'data-scs') !== false) {
            $snippets = Snippet::getActiveList();
            foreach ($snippets as $sn) {
                $text = $sn->findAndReplace($text);
            }
        }

        return $text;
    }
}