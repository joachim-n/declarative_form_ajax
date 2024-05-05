<?php

namespace Drupal\declarative_form_ajax_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * TODO: class docs.
 */
class DeclarativeAjaxTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'declarative_form_ajax_test_declarative_ajax_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['clickme'] = [
      '#type' => 'checkbox',
      '#title' => 'ticky',
      '#ajax' => [
        'callback' => '\Drupal\declarative_form_ajax\FormAjax::ajaxCallback',
        // 'wrapper' => $container_html_id,
      ],
    ];

    $form['replace-container'] = [
      '#type' => 'container',
      '#title' => 'container replaces! -- ' . time(),
      '#updates_on' => [
        ['clickme'],
      ],
    ];
    $form['replace-container']['inner-a'] = [
      '#type' => 'textfield',
      '#title' => 'inner A replaces! -- ' . time(),
    ];
    $form['replace-container']['inner-b'] = [
      '#type' => 'textfield',
      '#title' => 'inner B replaces! -- ' . time(),
    ];

    $form['fixed'] = [
      '#markup' => 'this does not change',
    ];


    $form['replace-details'] = [
      '#type' => 'details',
      '#title' => 'details replaces! -- ' . time(),
      '#open' => TRUE,
      '#updates_on' => [
        ['clickme'],
      ],
    ];
    $form['replace-details']['inner-a'] = [
      '#type' => 'textfield',
      '#title' => 'inner A replaces! -- ' . time(),
    ];
    $form['replace-details']['inner-b'] = [
      '#type' => 'textfield',
      '#title' => 'inner B replaces! -- ' . time(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
