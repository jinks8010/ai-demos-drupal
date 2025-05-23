<?php

namespace Drupal\Tests\viewfield\FunctionalJavascript;

/**
 * Tests Viewfield formatters.
 *
 * @group viewfield
 */
class ViewfieldFormatterTest extends ViewfieldFunctionalTestBase {

  /**
   * Test viewfield_default formatter.
   */
  public function testViewfieldFormatterDefault() {
    $this->form->setComponent('field_view_test', [
      'type' => 'viewfield_select',
    ])->save();

    $this->display->setComponent('field_view_test', [
      'type' => 'viewfield_default',
      'weight' => 1,
      'label' => 'hidden',
    ])->save();

    // Display creation form.
    $this->drupalGet('node/add/article_test');
    $session = $this->assertSession();

    $viewfield_target = $session->fieldExists("field_view_test[0][target_id]");
    $viewfield_display = $session->fieldExists("field_view_test[0][display_id]");

    // Set a random title for the node.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
    ];

    // Select a View.
    $viewfield_target->setValue('content_test');
    $session->assertWaitOnAjaxRequest();

    // Select a View Display.
    $viewfield_display->setValue('block_1');

    // Submit node form.
    $this->submitForm($edit, 'Save');
    $this->assertSession()->responseContains('Article 1');
    $this->assertSession()->responseContains('Page 1');
    $this->assertSession()->elementExists('css', '.views-element-container');
  }

  /**
   * Test viewfield_title formatter.
   */
  public function testViewfieldFormatterTitle() {
    $this->form->setComponent('field_view_test', [
      'type' => 'viewfield_select',
    ])->save();

    $this->display->setComponent('field_view_test', [
      'type' => 'viewfield_title',
      'weight' => 1,
      'label' => 'hidden',
    ])->save();

    // Display creation form.
    $this->drupalGet('node/add/article_test');
    $session = $this->assertSession();

    $viewfield_target = $session->fieldExists("field_view_test[0][target_id]");
    $viewfield_display = $session->fieldExists("field_view_test[0][display_id]");

    // Set a random title for the node.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
    ];

    // Select a View.
    $viewfield_target->setValue('content_test');
    $session->assertWaitOnAjaxRequest();

    // Select a View Display.
    $viewfield_display->setValue('block_1');

    // Submit node form.
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('View: (content_test)');
    $this->assertSession()->pageTextContains('Display: Block (block_1)');
  }

  /**
   * Test viewfield_default formatter.
   */
  public function testViewfieldFormatterRendered() {
    // We need a cardinality of 1.
    $this->container->get('entity_type.manager')
      ->getStorage('field_storage_config')
      ->load('node.field_view_test')
      ->setCardinality(1)
      ->save();

    $this->form->setComponent('field_view_test', [
      'type' => 'viewfield_select',
    ])->save();

    $this->display->setComponent('field_view_test', [
      'type' => 'viewfield_rendered',
      'weight' => 1,
      'label' => 'hidden',
    ])->save();

    // Display creation form.
    $this->drupalGet('node/add/article_test');
    $session = $this->assertSession();

    $viewfield_target = $session->fieldExists("field_view_test[0][target_id]");
    $viewfield_display = $session->fieldExists("field_view_test[0][display_id]");

    // Set a random title for the node.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
    ];

    // Select a View.
    $viewfield_target->setValue('content_test');
    $session->assertWaitOnAjaxRequest();

    // Select a View Display.
    $viewfield_display->setValue('block_1');

    // Submit node form.
    $this->submitForm($edit, 'Save');
    $this->assertSession()->responseContains('Article 1');
    $this->assertSession()->responseContains('Page 1');
    // The view itself should not be rendered.
    $this->assertSession()->elementNotExists('css', '.views-element-container');
    $this->assertSession()->elementNotExists('css', '.views-row');
    // Check that the markup is nested correctly, as if it's a multivalued field.
    $this->assertSession()->elementTextContains('css', 'article > div > div > div > article', 'Page 1');
  }

  /**
   * Test Viewfield argument handling.
   */
  public function testViewfieldArgumentHandling() {
    $this->form->setComponent('field_view_test', [
      'type' => 'viewfield_select',
    ])->save();

    $this->display->setComponent('field_view_test', [
      'type' => 'viewfield_default',
      'weight' => 1,
      'label' => 'hidden',
    ])->save();

    // Display creation form.
    $this->drupalGet('node/add/article_test');
    $session = $this->assertSession();

    $viewfield_target = $session->fieldExists("field_view_test[0][target_id]");
    $viewfield_display = $session->fieldExists("field_view_test[0][display_id]");
    $viewfield_arguments = $session->fieldExists("field_view_test[0][arguments]");

    // Select a View.
    $viewfield_target->setValue('content_test');
    $session->assertWaitOnAjaxRequest();

    // Select a display from the View.
    $viewfield_display->setValue('block_1');

    // Open the details element so we can fill in an argument.
    $this->click('#field-view-test-values details');
    $viewfield_arguments->setValue('page_test');

    // Fill in a random title.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
    ];

    // Submit node form.
    $this->submitForm($edit, 'Save');

    // Test results to verify that only page nodes are shown.
    $this->assertSession()->responseContains('Page 1');
    $this->assertSession()->responseNotContains('Article 1');
  }

  /**
   * Test Viewfield "Items to display" override.
   */
  public function testViewfieldItemsToDisplay() {
    $this->form->setComponent('field_view_test', [
      'type' => 'viewfield_select',
    ])->save();

    $this->display->setComponent('field_view_test', [
      'type' => 'viewfield_default',
      'weight' => 1,
      'label' => 'hidden',
    ])->save();

    // Display creation form.
    $this->drupalGet('node/add/article_test');
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $viewfield_target = $session->fieldExists("field_view_test[0][target_id]");
    $viewfield_display = $session->fieldExists("field_view_test[0][display_id]");
    $viewfield_items = $session->fieldExists("field_view_test[0][items_to_display]");

    // Select a View.
    $viewfield_target->setValue('content_test');
    $session->assertWaitOnAjaxRequest();

    // Select a display from the View.
    $viewfield_display->setValue('block_1');

    // Open the details element so we can fill in an argument.
    $this->click('#field-view-test-values details');
    $viewfield_items->setValue('2');

    // Fill in a random title.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
    ];

    // Submit node form.
    $this->submitForm($edit, 'Save');

    // Test results to verify that only page nodes are shown.
    $rows = $page->findAll('css', '.views-element-container div .views-row');
    $this->assertCount(2, $rows);
  }

  /**
   * Test Viewfield "Empty" view results.
   */
  public function testViewfieldEmptyView() {
    $this->form->setComponent('field_view_test', [
      'type' => 'viewfield_select',
    ])->save();

    $this->display->setComponent('field_view_test', [
      'type' => 'viewfield_default',
      'weight' => 1,
      'label' => 'hidden',
    ])->save();

    // Display creation form.
    $this->drupalGet('node/add/article_test');
    $session = $this->assertSession();

    $viewfield_target = $session->fieldExists("field_view_test[0][target_id]");
    $viewfield_display = $session->fieldExists("field_view_test[0][display_id]");
    $viewfield_arguments = $session->fieldExists("field_view_test[0][arguments]");

    // Select a View.
    $viewfield_target->setValue('content_test');
    $session->assertWaitOnAjaxRequest();

    // Select a display from the View.
    $viewfield_display->setValue('block_1');

    // Open the details element so we can fill in an argument.
    $this->click('#field-view-test-values details');
    $viewfield_arguments->setValue('content_type_null');

    // Fill in a random title.
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
    ];

    // Submit node form.
    $this->submitForm($edit, 'Save');

    // Test results to verify that only page nodes are shown.
    $this->assertSession()->elementNotExists('css', 'div.field--name-field-view-test');
  }

}
