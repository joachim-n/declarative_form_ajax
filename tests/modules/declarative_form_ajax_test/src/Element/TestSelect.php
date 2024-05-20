<?php

namespace Drupal\declarative_form_ajax_test\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElement;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test custom form element which has its own built-in AJAX update.
 *
 * @RenderElement("declarative_form_ajax_test_select")
 */
class TestSelect extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = static::class;

    return [
      '#process' => [
        [$class, 'processPlugin'],
      ],
    ];
  }

  /**
   * Process callback.
   */
  public static function processPlugin(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#tree'] = TRUE;

    $container_html_id = Html::getUniqueId('select-ajax');

    // Allow forms to place elements that end up inside this element once it is
    // built. This is the same behaviour as core's radios and checkboxes
    // elements.
    $element += ['container' => []];

    $element['container'] += [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $element['#title'] ?? '',
      '#description' => $element['#description'] ?? '',
      '#attributes' => ['id' => $container_html_id],
    ];

    $element['container']['select'] = [
      '#type' => 'select',
      '#title' => t("Select"),
      '#options' => [
        'red' => 'Red',
        'blue' => 'Blue',
        'green' => 'Green',
      ],
      '#empty_value' => '',
      '#ajax' => [
        'callback' => get_class() . '::pluginDropdownCallback',
        'wrapper' => $container_html_id,
        'options' => [
          // Pass the array parents to the AJAX callback in a query parameter,
          // so that it can determine where in the form our element is located.
          'query' => [
            'element_parents' => implode('/', $element['#array_parents']),
          ],
        ],
      ],
    ];

    $element['container']['update'] = [
      '#type' => 'textfield',
      '#title' => 'element replaces! -- ' . time(),
    ];

    return $element;
  }

  /**
   * AJAX callback for the plugin ID select element.
   */
  public static function pluginDropdownCallback(&$form, FormStateInterface &$form_state, Request $request) {
    $form_parents = explode('/', $request->query->get('element_parents'));

    // Sanitize form parents before using them.
    $form_parents = array_filter($form_parents, [Element::class, 'child']);

    // Retrieve the element to be rendered.
    $form = NestedArray::getValue($form, $form_parents);

    return $form;
  }

}
