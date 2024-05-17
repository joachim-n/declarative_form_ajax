<?php

namespace Drupal\declarative_form_ajax;

use Drupal\Core\Render\Element as RenderElement;

/**
 * Provides helpers for working with form elements.
 *
 * @todo This should be merged with core's Element class.
 */
class Element {

  /**
   * Applies a callback to an element and all of its children at all depths.
   *
   * @todo: Consider allowing additional callback parameters to be passed in to
   * this method when adding it to core.
   *
   * @param array $element
   *   The form element.
   * @param callable $callback
   *   The callback to apply.
   */
  public static function walkChildrenRecursive(array $element, callable $callback) {
    // Call the callback on the element.
    $callback($element);

    foreach (RenderElement::children($element) as $key) {
      static::walkChildrenRecursive($element[$key], $callback);
    }
  }

}
