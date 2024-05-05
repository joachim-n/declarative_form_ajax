<?php

namespace Drupal\declarative_form_ajax;

use Drupal\Core\Render\Element as RenderElement;

/**
 * Provides helpers TODO.
 */
class Element {

  public static function walkChildrenRecursive(array $element, callable $callback) {
    // Call the callback on the element.
    $callback($element);

    foreach (RenderElement::children($element) as $key) {
      static::walkChildrenRecursive($element[$key], $callback);
    }
  }

}
