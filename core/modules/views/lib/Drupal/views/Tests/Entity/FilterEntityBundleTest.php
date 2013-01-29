<?php

/**
 * @file
 * Contains \Drupal\views\Tests\Entity\FilterEntityBundleTest.
 */

namespace Drupal\views\Tests\Entity;

use Drupal\views\Tests\ViewTestBase;

/**
 * Tests the EntityType generic filter handler.
 */
class FilterEntityBundleTest extends ViewTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_entity_type_filter');

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  /**
   * Entity info data.
   *
   * @var array
   */
  protected $entityInfo;

  /**
   * An array of entities.
   *
   * @var array
   */
  protected $entities = array();

  public static function getInfo() {
    return array(
      'name' => 'Filter: Entity bundle',
      'description' => 'Tests the generic entity bundle filter.',
      'group' => 'Views Handlers',
    );
  }

  public function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'test_bundle'));
    $this->drupalCreateContentType(array('type' => 'test_bundle_2'));

    $this->entityInfo = entity_get_info('node');

    $this->entities['count'] = 0;

    foreach ($this->entityInfo['bundles'] as $key => $info) {
      for ($i = 0; $i < 5; $i++) {
        $entity = entity_create('node', array('label' => $this->randomName(), 'uid' => 1, 'type' => $key));
        $entity->save();
        $this->entities[$key][$entity->id()] = $entity;
        $this->entities['count']++;
      }
    }
  }

  /**
   * Tests the generic bundle filter.
   */
  public function testFilterEntity() {
    $view = views_get_view('test_entity_type_filter');
    $this->executeView($view);

    // Test we have all the results, with all types selected.
    $this->assertEqual(count($view->result), $this->entities['count']);

    // Test the value_options of the filter handler.
    $expected = array();

    foreach ($this->entityInfo['bundles'] as $key => $info) {
      $expected[$key] = $info['label'];
    }
    $this->assertIdentical($view->filter['type']->get_value_options(), $expected);

    $view->destroy();

    // Test each bundle type.
    foreach ($this->entityInfo['bundles'] as $key => $info) {
      // Test each bundle type.
      $view->initDisplay();
      $filters = $view->display_handler->getOption('filters');
      $filters['type']['value'] = drupal_map_assoc(array($key));
      $view->display_handler->setOption('filters', $filters);
      $this->executeView($view);

      $this->assertEqual(count($view->result), count($this->entities[$key]));

      $view->destroy();
    }

    // Test an invalid bundle type to make sure we have no results.
    $view->initDisplay();
    $filters = $view->display_handler->getOption('filters');
    $filters['type']['value'] = drupal_map_assoc(array('type_3'));
    $view->display_handler->setOption('filters', $filters);
    $this->executeView($view);

    $this->assertEqual(count($view->result), 0);
  }

}