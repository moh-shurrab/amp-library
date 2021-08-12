<?php
/*
 * Copyright 2016 Google
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Lullabot\AMP\Pass;

use QueryPath\DOMQuery;

use Lullabot\AMP\Utility\ActionTakenLine;
use Lullabot\AMP\Utility\ActionTakenType;

/**
 * Class TiktokTransformPass
 * @package Lullabot\AMP\Pass
 */
class TiktokTransformPass extends BasePass
{
    const DEFAULT_TIKTOK_HEIGHT = 400;
    const DEFAULT_TIKTOK_WIDTH = 400;

    function pass()
    {
        $all_tiktok = $this->q->find('blockquote.tiktok-embed');
        /** @var DOMQuery $el */
        foreach ($all_tiktok as $el) {
            /** @var \DOMElement $dom_el */
            $dom_el = $el->get(0);
            $lineno = $this->getLineNo($dom_el);
           $shortcode = $dom_el->getAttribute( 'data-video-id' );
            // If we can't get the tiktok shortcode, abort
            if (empty($shortcode)) {
                continue;
            }
                
            $context_string = $this->getContextString($dom_el);
            $instagram_script_tag = $this->getScriptTag($el, '&(*UTF8)tiktok.com/embed.js&i');
            /** @var \DOMElement $new_dom_el */
            $el->after('<amp-tiktok width="325" height="575" data-src="6718335390845095173" layout="responsive"></amp-tiktok>');

            $new_el = $el->next();

            // Set shortcode and use oembed to get the image size parameters
            // Set caption, if it has.
            $this->setInstagramCaptioned($el, $new_el);

            $new_dom_el = $new_el->get(0);
            // Remove the blockquote, its children and the instagram script tag that follows after the blockquote
            $el->removeChildren()->remove();
            if (!empty($instagram_script_tag)) {
                $instagram_script_tag->remove();
                $this->addActionTaken(new ActionTakenLine('blockquote.tiktok-embed (with associated script tag)', ActionTakenType::INSTAGRAM_CONVERTED, $lineno, $context_string));
            } else {
                $this->addActionTaken(new ActionTakenLine('blockquote.tiktok-embed', ActionTakenType::INSTAGRAM_CONVERTED, $lineno, $context_string));
            }
            $this->context->addLineAssociation($new_dom_el, $lineno);
        }
        return $this->transformations;
    }

   

    /**
     * If the instragram to embed has caption, set the instagram caption attribute
     *
     * @param DOMQuery $el
     * @param DOMQuery $new_el
     * @return string|null
     */
    protected function setInstagramCaptioned(DOMQuery $el, DOMQuery $new_el)
    {
        if ($el->hasAttr('data-instgrm-captioned')) {
            $new_el->attr('data-captioned', true);
        }

        return null;
    }

}
