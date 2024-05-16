<?php

namespace Drupal\declarative_form_ajax;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the form AJAX handler for declarative AJAX.
 */
class FormAjax {

  /**
   * Form AJAX handler.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public static function ajaxCallback(&$form, FormStateInterface &$form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $triggering_element_parents = $triggering_element['#array_parents'];

    // Walk the entire form recursively, looking for elements which say they
    // update on the triggering element.
    $collected_elements = [];
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
    // dsm($collected_elements);
    // dsm('!');

    $response = new AjaxResponse();

    foreach ($collected_elements as $updated_element) {
      // return $updated_element;

      // If the
      // element is part of the group (#group is set on it) it won't be rendered
      // unless we remove #group from it. This is caused by
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

    // $response->addCommand(new InsertCommand('#block-olivero-page-title', '<p>POOP</p>'));

    return $response;
  }

}
