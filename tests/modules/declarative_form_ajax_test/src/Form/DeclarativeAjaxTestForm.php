<?php

namespace Drupal\declarative_form_ajax_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\declarative_form_ajax\FormAjax;

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
      // '#ajax' => [
      //   'callback' => '\Drupal\declarative_form_ajax\FormAjax::ajaxCallback',
      // ],
    ];

    // Container that will be updated by the 'clickme' checkbox.
    $form['replace-container'] = [
      '#type' => 'container',
      '#title' => 'container replaces! -- ' . time(),
      '#ajax' => [
        'updated_by' => [
          ['clickme'],
        ],
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

    // Details that will be updated by the 'clickme' checkbox.
    $form['replace-details'] = [
      '#type' => 'details',
      '#title' => 'details replaces! -- ' . time(),
      '#open' => TRUE,
      '#ajax' => [
        'updated_by' => [
          ['clickme'],
        ],
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

    $form['#after_build'][] = FormAjax::class .  '::ajaxAfterBuild';

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
