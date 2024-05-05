<?php

namespace Drupal\Tests\declarative_form_ajax\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test case class TODO.
 *
 * @group declarative_form_ajax
 */
class DeclarativeFormAjaxText extends WebDriverTestBase {

  /**
   * The modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'declarative_form_ajax',
    'declarative_form_ajax_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

  }

  /**
   * Tests the TODO.
   */
  public function testMyTest() {
    // TODO: test code here.
  }

}
