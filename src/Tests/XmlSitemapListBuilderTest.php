<?php

/**
 * @file
 * Contains \Drupal\xmlsitemap\Tests\XmlSitemapListBuilderTest.
 */

namespace Drupal\xmlsitemap\Tests;

use Drupal\Core\Language\Language;

/**
 * Tests the sitemaps list builder.
 */
class XmlSitemapListBuilderTest extends XmlSitemapTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'xmlsitemap', 'node', 'locale', 'content_translation', 'system');

  public static function getInfo() {
    return array(
      'name' => 'XML sitemap list builder tests',
      'description' => 'List builder tests for the XML sitemap module.',
      'group' => 'XML sitemap',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->admin_user = $this->drupalCreateUser(array('administer languages', 'access administration pages', 'administer site configuration', 'administer xmlsitemap', 'access content'));
    $this->drupalLogin($this->admin_user);

    $this->languageManager = \Drupal::languageManager();
    if (!$this->languageManager->getLanguage('fr')) {
      // Add a new language.
      $language = new Language(array(
        'id' => 'fr',
        'name' => 'French',
      ));
      language_save($language);
    }

    if (!$this->languageManager->getLanguage('en')) {
      // Add a new language.
      $language = new Language(array(
        'id' => 'en',
        'name' => 'English',
      ));
      language_save($language);
    }
    $edit = array(
      'site_default_language' => 'en',
    );
    $this->drupalPostForm('admin/config/regional/settings', $edit, t('Save configuration'));
    // Enable URL language detection and selection.
    $edit = array('language_interface[enabled][language-url]' => '1');
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));
  }

  /**
   * Test if the default sitemap exists.
   */
  public function testDefaultSitemap() {
    $this->drupalLogin($this->admin_user);
    $context = array();
    $id = xmlsitemap_sitemap_get_context_hash($context);

    $this->drupalGet('admin/config/search/xmlsitemap');
    $this->assertText($id);
  }

  /**
   * Test if multiple sitemaps exist and have consistent information.
   */
  public function testMoreSitemaps() {
    $this->drupalLogin($this->admin_user);
    $edit = array(
      'label' => 'English',
      'context[language]' => 'en'
    );
    $this->drupalPostForm('admin/config/search/xmlsitemap/add', $edit, t('Save'));
    $context = array('language' => 'en');
    $id = xmlsitemap_sitemap_get_context_hash($context);
    $this->assertText(t('Saved the English sitemap.'));
    $this->assertText($id);

    $edit = array(
      'label' => 'French',
      'context[language]' => 'fr'
    );
    $this->drupalPostForm('admin/config/search/xmlsitemap/add', $edit, t('Save'));
    $context = array('language' => 'fr');
    $id = xmlsitemap_sitemap_get_context_hash($context);
    $this->assertText(t('Saved the French sitemap.'));
    $this->assertText($id);

    $this->drupalPostForm('admin/config/search/xmlsitemap/add', $edit, t('Save'));
    $this->assertText(t('There is another sitemap saved with the same context.'));

    $edit = array(
      'label' => 'Undefined',
      'context[language]' => 'und'
    );
    $this->drupalPostForm('admin/config/search/xmlsitemap/add', $edit, t('Save'));
    $this->assertText(t('There is another sitemap saved with the same context.'));

    $sitemaps = entity_load_multiple('xmlsitemap');
    foreach ($sitemaps as $sitemap) {
      $label = $sitemap->label();
      $this->drupalPostForm("admin/config/search/xmlsitemap/{$sitemap->id()}/delete", array(), t('Delete'));
      $this->assertRaw(t("Sitemap :label has been deleted.", array(':label' => $label)));
    }

    $sitemaps = entity_load_multiple('xmlsitemap');
    $this->assertEqual(count($sitemaps), 0, t('No more sitemaps.'));
  }

}
