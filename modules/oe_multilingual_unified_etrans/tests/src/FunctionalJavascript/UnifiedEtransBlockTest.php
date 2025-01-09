<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_multilingual_unified_etrans\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the unified translations via webtool.
 */
class UnifiedEtransBlockTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'node',
    'oe_multilingual_unified_etrans',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType([
      'name' => 'Page',
      'type' => 'page',
    ]);

    $this->drupalPlaceBlock('language_block', ['region' => 'navbar_right']);
    $this->drupalPlaceBlock('oe_multilingual_unified_etrans', ['region' => 'highlighted']);
  }

  /**
   * Tests the unified eTranslation block.
   */
  public function testUnifiedEtransBlockDisplay(): void {
    // Navigate to system 404.
    $this->drupalGet('/test');
    $this->assertSession()->addressEquals('/en/test');
    // Default language, block not present.
    $this->assertSession()->pageTextNotContains("English is available via eTranslation, the European Commission's machine translation service.");
    $this->drupalGet('/test', [
      'language' => ConfigurableLanguage::load('fr'),
    ]);
    $this->assertSession()->addressEquals('/fr/test');
    // Non-default language, block present.
    $this->assertSession()->pageTextContains("French is available via eTranslation, the European Commission's machine translation service.");
    $this->assertSession()->pageTextContains("Translate to French");
    $this->assertSession()->pageTextContains("Important information about machine translation");

    // Create a node with english and french translations.
    $node = $this->drupalCreateNode([
      'title' => 'English translation',
      'body' => [
        'value' => "I'm a text that will be translated.",
        'format' => filter_default_format(),
      ],
    ]);
    $translation = $node->addTranslation('fr', [
      'title' => 'Traduction Française',
      'body' => [
        'value' => "Je suis un texte qui va être traduit.",
        'format' => filter_default_format(),
      ],
    ]);
    $translation->save();

    // English translation should not have the block.
    $this->drupalGet($node->toUrl(), [
      'language' => ConfigurableLanguage::load('en'),
    ]);
    $this->assertSession()->pageTextContains("I'm a text that will be translated.");
    $this->assertSession()->pageTextNotContains("English is available via eTranslation, the European Commission's machine translation service.");
    // French translation should not have the block.
    $this->drupalGet($node->toUrl(), [
      'language' => ConfigurableLanguage::load('fr'),
    ]);
    $this->assertSession()->pageTextContains("Je suis un texte qui va être traduit.");
    $this->assertSession()->pageTextNotContains("French is available via eTranslation, the European Commission's machine translation service.");
    // Croatian translation should have the block
    // and display the english text.
    $this->drupalGet($node->toUrl(), [
      'language' => ConfigurableLanguage::load('hr'),
    ]);
    $this->assertSession()->pageTextContains("I'm a text that will be translated.");
    $this->assertSession()->pageTextContains("Croatian is available via eTranslation, the European Commission's machine translation service.");

  }

  /**
   * Tests that unified eTranslation block is dismissable.
   */
  public function testUnifiedEtransBlockDismissable(): void {
    $this->drupalGet('/test', [
      'language' => ConfigurableLanguage::load('fr'),
    ]);
    $this->assertSession()->pageTextContains("French is available via eTranslation, the European Commission's machine translation service.");
    $page = $this->getSession()->getPage();
    $page->find('css', '.utr-close')->click();
    $this->assertSession()->pageTextNotContains("French is available via eTranslation, the European Commission's machine translation service.");
  }

  /**
   * Tests that we can translate content.
   */
  public function testUnifiedEtransBlockTranslation(): void {
    $this->drupalGet('/test', [
      'language' => ConfigurableLanguage::load('fr'),
    ]);
    $this->assertSession()->pageTextContains("The requested page could not be found.");
    $page = $this->getSession()->getPage();
    $page->find('css', '.oe-multilingual-unified-etrans--translate')->click();
    sleep(15);
    $this->assertSession()->pageTextContains("La page demandée n'a pas pu être trouvée.");
  }

}
