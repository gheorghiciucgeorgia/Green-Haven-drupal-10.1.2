<?php

declare(strict_types=1);

namespace Drupal\Tests\bootstrap_simple_carousel\Unit\Service;

use Drupal\bootstrap_simple_carousel\Service\CarouselService;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\file\FileStorageInterface;
use Drupal\Tests\PhpunitCompatibilityTrait;
use Drupal\Tests\UnitTestCase;

/**
 *
 *
 * @coversDefaultClass \Drupal\bootstrap_simple_carousel\Service\CarouselService
 *
 * @group bootstrap_simple_carousel
 */
class CarouselServiceTest extends UnitTestCase {
  use PhpunitCompatibilityTrait;
  /**
   * The mocked renderer.
   */
  protected RendererInterface $renderer;
  /**
   * The mocked file.
   */
  protected FileStorageInterface $file;
  /**
   * The mocked entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->renderer = $this->createMock(RendererInterface::class);
    $this->file = $this->createMock(FileStorageInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('getStorage')->with('file')->willReturn($this->file);
  }

  /**
   * Tests the renderLink() method.
   *
   * @covers ::renderLink
   *
   * @dataProvider providerTestRenderLink
   */
  public function testRenderLink(Url $url, TranslatableMarkup $title, array $attributes, array $with, string $expected): void {
    $this->renderer->expects($this->once())->method('render')->with($with)->willReturn(new FormattableMarkup($expected, []));
    $carouselService = new CarouselService($this->renderer, $this->entityTypeManager);
    $actual = $carouselService->renderLink($url, $title, $attributes);
    $this->assertSame($expected, (string) $actual);
  }

  /**
   * Tests the renderImageById() method.
   *
   * @covers ::renderImageById
   *
   * @dataProvider providerTestRenderImageById
   */
  public function testRenderImageById(int $imageId, $file, string $imageStyle, array $params, array $with, int $renderCount, string $expected): void {
    $this->file->expects($this->once())->method('load')->with($imageId)->willReturn($file);
    $this->renderer->expects($this->exactly($renderCount))->method('render')->with($with)->willReturn(new HtmlEscapedText($expected));
    $carouselService = new CarouselService($this->renderer, $this->entityTypeManager);
    $actual = $carouselService->renderImageById($imageId, $imageStyle, $params);
    $this->assertEquals($expected, (string) $actual);
  }

  /**
   * Tests the getStatuses() method.
   *
   * @covers ::getStatuses
   */
  public function testGetStatuses(): void {
    $stringTranslation = $this->createMock(TranslationInterface::class);
    $carouselService = new CarouselService($this->renderer, $this->entityTypeManager);
    $carouselService->setStringTranslation($stringTranslation);
    $this->assertSame(count(['Inactive', 'Active']), count($carouselService->getStatuses()));
  }

  /**
   * Provides test data for providerTestRenderImageById.
   */
  public function providerTestRenderImageById(): array {
    $url = 'public://directory/file.jpg';
    $file = $this->createMock(FileInterface::class);
    $file->expects($this->once())->method('getFileUri')->willReturn($url);
    $imageStyle = 'style_slider';
    $params = ['alt' => 'alt', 'title' => 'title'];
    $with = [
      '#theme' => 'image_style',
      '#style_name' => $imageStyle,
      '#uri' => $url,
      '#alt' => $params['alt'],
      '#title' => $params['title'],
    ];
    return [
      [5, $file, $imageStyle, $params, $with, 1, '/directory/file.jpg'],
      [33, NULL, $imageStyle, $params, $with, 0, ''],
    ];
  }

  /**
   * Provides test data for providerTestRenderLink.
   */
  public function providerTestRenderLink(): array {
    $url = Url::fromUri('http://example.com');
    $title = new TranslatableMarkup('example');
    $expected = '<a href="http://example.com">example</a>';
    $with = [
      '#type' => 'link',
      '#title' => $title,
      '#url' => $url,
      '#options' => ['attributes' => [], 'html' => FALSE],
    ];
    return [[$url, $title, [], $with, $expected]];
  }

}
