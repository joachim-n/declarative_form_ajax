<?php

namespace Drupal\declarative_form_ajax;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\RenderElement;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the form AJAX handlers for declarative AJAX.
 */
class FormAjax {

  /**
   * After build callback to set up declarative AJAX.
   *
   * This should be set as an '#after_build' callback on the whole form.
   *
   * This walks the entire form, looking for elements with
   * ['#ajax']['updated_by'] set. It then sets up the targeted elements to be
   * AJAX triggers.
   *
   * This needs to do a few hacks because it's operating outside of core
   * FormAPI:
   *  - AJAX has already been processed by this point, so we need to forcibly
   *    process it again.
   *  - In an AJAX request, the form build process calls setTriggeringElement()
   *    before #after_build callbacks are executed, and does not set it by
   *    reference, and so we need to update the form state's copy so it has our
   *    callback.
   */
  public static function ajaxAfterBuild($form, FormStateInterface $form_state) {
    $ajax_triggering_elements = [];

    // Find all elements which are the target of an AJAX updating element.
    Element::walkChildrenRecursive($form, function($walk_element) use (&$form, $form_state, &$ajax_triggering_elements) {
      if (!isset($walk_element['#ajax']['updated_by'])) {
        return;
      }

      foreach ($walk_element['#ajax']['updated_by'] as $address) {
        // Use a key based on the element address so we only process each
        // element once. This is important so we retain any existing AJAX
        // callback on an element.
        $ajax_triggering_elements[implode(':', $address)] = $address;
      }
    });

    // Set up each triggering element.
    foreach ($ajax_triggering_elements as $address) {
      $triggering_element =& NestedArray::getValue($form, $address);

      if (!isset($triggering_element['#ajax_processed'])) {
        // TODO: throw an exception, this triggering element won't work with
        // AJAX!
      }
      elseif (($triggering_element['#ajax_processed']) == FALSE) {
        $triggering_element['#ajax']['callback'] = static::class . '::ajaxCallback';

        // The element was already processed for AJAX by
        // RenderElement::processAjaxForm() but there were no AJAX settings at
        // the time, so we need to send it through again.
        unset($triggering_element['#ajax_processed']);
        $triggering_element = RenderElement::processAjaxForm($triggering_element, $form_state, $form);

        // During an AJAX request, the triggering element is set on the form
        // state earlier than the #after_build process, and so will not have
        // our callback. Therefore, we need to set it again.
        if ($current_triggering_element =& $form_state->getTriggeringElement()) {
          if ($current_triggering_element['#array_parents'] == $triggering_element['#array_parents']) {
            $current_triggering_element['#ajax']['callback'] = static::class . '::ajaxCallback';
          }
        }
      }
      elseif (($triggering_element['#ajax_processed']) == TRUE) {
        // The element has its own AJAX callback, which we need to register so
        // that we call it from our own callback.
        $triggering_element['#ajax']['prior_callback'] = $triggering_element['#ajax']['callback'];

        $triggering_element['#ajax']['callback'] = static::class . '::ajaxCallback';

        // During an AJAX request, the triggering element is set on the form
        // state earlier than the #after_build process, and so will not have
        // our callback. Therefore, we need to set it again.
        if ($current_triggering_element =& $form_state->getTriggeringElement()) {
          if ($current_triggering_element['#array_parents'] == $triggering_element['#array_parents']) {
            $current_triggering_element['#ajax']['prior_callback'] = $current_triggering_element['#ajax']['callback'];

            $current_triggering_element['#ajax']['callback'] = static::class . '::ajaxCallback';
          }
        }
      }
    }

    return $form;
  }

  /**
   * Form AJAX handler.
   *
   * This is set as an AJAX callback on AJAX elements by
   * static::ajaxAfterBuild().
   *
   * If an AJAX element already had an AJAX callback, this takes care of calling
   * it and integrating the result from that into our response.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public static function ajaxCallback(&$form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $triggering_element_parents = $triggering_element['#array_parents'];

    // Call a prior callback that we've replaced.
    // We take its response and use that, as it's not possible to copy AJAX
    // commands from one AjaxResponse to another.
    if (isset($triggering_element['#ajax']['prior_callback'])) {
      $prior_callback = $triggering_element['#ajax']['prior_callback'];
      $prior_callback = $form_state->prepareCallback($prior_callback);

      // Make a copy of the form array, as the callback apparently messes with it.
      $form_copy = $form;
      $prior_callback_result = call_user_func_array($prior_callback, [&$form_copy, &$form_state, $request]);

      if ($prior_callback_result instanceof AjaxResponse) {
        $response = $prior_callback_result;
      }
      else {
        $route_match = \Drupal::routeMatch();

        $response = \Drupal::service('main_content_renderer.ajax')->renderResponse($prior_callback_result, $request, $route_match);
      }
    }
    else {
      $response = new AjaxResponse();
    }

    // Elements from the form that should be rendered and returned in the
    // response.
    $collected_elements = [];

    // Walk the entire form recursively, looking for elements which say they
    // update on the triggering element.
    Element::walkChildrenRecursive($form, function($element) use ($triggering_element_parents, &$collected_elements) {
      if (!isset($element['#ajax']['updated_by'])) {
        return;
      }

      foreach ($element['#ajax']['updated_by'] as $updated_by) {
        if ($updated_by == $triggering_element_parents) {
          $collected_elements[] = $element;
        }
      }
    });

    // Render each element that should update and add it to the response.
    foreach ($collected_elements as $updated_element) {
      // If the element is part of the group (#group is set on it) it won't be
      // rendered unless we remove #group from it. This is caused by
      // \Drupal\Core\Render\Element\RenderElement::preRenderGroup(), which
      // prevents all members of groups from being rendered directly.
      if (!empty($updated_element['#group'])) {
        unset($updated_element['#group']);
      }

      $html = \Drupal::service('renderer')->renderRoot($updated_element);
      $response->addAttachments($updated_element['#attached']);

      // // TODO: This bit feels a bit brittle.
      // // But if this is all integrated into FormAPI, then the process step can
      // // take care of adding wrapper DIVs around any form elements that have
      // // #update_on.

      // $doc = new \DOMDocument();
      // $doc->loadHtml("<html><body>" . $html . "</body></html>");
      // $element_dom = $doc->getElementsByTagName('body')->item(0)->firstElementChild;
      // if ($element_id = $element_dom->getAttribute('id')) {
      //   $insert_selector = "#{$element_id}";
      // }
      // elseif ($element_data_selector = $element_dom->getAttribute('data-drupal-selector')) {
      //   $insert_selector = ".form-item:has(*[data-drupal-selector=\"{$element_data_selector}\"])";
      // }
      // elseif ($element_classes = $element_dom->getAttribute('class')) {
      //   // Scraping the bottom of the barrel here!
      //   $insert_selector = '.'. implode('.', $element_classes);
      // }

      // dump($element_dom->getAttribute('id'));
      // dump($element_dom->getAttribute('class'));
      // dump($element_dom->getAttribute('data-drupal-selector'));

      $element_data_selector = $updated_element['#attributes']['data-drupal-selector'];
      // For single elements. Maybe don't support?
      // $insert_selector = ".form-item:has(*[data-drupal-selector=\"{$element_data_selector}\"])";

      $insert_selector = "*[data-drupal-selector=\"{$element_data_selector}\"]";

      $response->addCommand(new InsertCommand($insert_selector, $html));
    }

    $status_messages = ['#type' => 'status_messages'];
    $output = \Drupal::service('renderer')->renderRoot($status_messages);
    if (!empty($output)) {
      // TODO! doesn't work!
      $response->addCommand(new PrependCommand(NULL, $output));
    }

    return $response;
  }

}
