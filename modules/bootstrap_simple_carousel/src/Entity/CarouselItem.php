<?php

declare(strict_types=1);

namespace Drupal\bootstrap_simple_carousel\Entity;

use Drupal\bootstrap_simple_carousel\CarouselItemInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the carousel item entity class.
 *
 * @ContentEntityType(
 *   id = "bootstrap_simple_carousel",
 *   label = @Translation("Carousel Item"),
 *   label_collection = @Translation("Carousel Items"),
 *   label_singular = @Translation("carousel item"),
 *   label_plural = @Translation("carousel items"),
 *   label_count = @PluralTranslation(
 *     singular = "@count carousel item",
 *     plural = "@count carousel items"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\bootstrap_simple_carousel\CarouselItemStorage",
 *     "form" = {
 *       "delete" = "Drupal\bootstrap_simple_carousel\Form\DeleteForm",
 *       "edit" = "Drupal\bootstrap_simple_carousel\Form\EditForm",
 *     }
 *   },
 *   base_table = "bootstrap_simple_carousel",
 *   entity_keys = {
 *     "id" = "cid",
 *     "image_id" = "image_id",
 *     "image_alt" = "image_alt",
 *     "image_title" = "image_title",
 *     "image_link" = "image_link",
 *     "caption_title" = "caption_title",
 *     "caption_text" = "caption_text",
 *     "weight" = "weight",
 *     "status" = "status",
 *   },
 *   links = {
 *     "settings-form" = "/admin/config/media/bootstrap_simple_carousel",
 *     "delete-form" = "/admin/structure/bootstrap_simple_carousel/delete/{id}",
 *     "create" = "/admin/structure/bootstrap_simple_carousel/add",
 *     "edit-form" = "/admin/structure/bootstrap_simple_carousel/edit/{id}",
 *     "list" = "/admin/structure/bootstrap_simple_carousel",
 *   }
 * )
 */
class CarouselItem extends ContentEntityBase implements CarouselItemInterface {

  /**
   * Return an entity value by name.
   */
  private function getValue(string $field_name) {
    if (!isset($this->values[$field_name][$this->activeLangcode])) {
      $list = $this->getTranslatedField($field_name, $this->activeLangcode)->first();
      return $list ? $list->getString() : NULL;
    }
    return $this->values[$field_name][$this->activeLangcode] ?? NULL;
  }

  /**
   * Set an entity value by name.
   */
  private function setValue(string $field_name, $field_value): self {
    if (isset($this->values[$field_name])) {
      $this->values[$field_name][$this->activeLangcode] = $field_value;
    }

    return $this;
  }

  /**
   * Get Image id.
   */
  public function getImageId(): ?int {
    return $this->getValue('image_id') ? (int) $this->getValue('image_id') : NULL;

  }

  /**
   * Set Image id.
   */
  public function setImageId(int $imageId): self {
    return $this->setValue('image_id', $imageId);
  }

  /**
   * Get Alt Image.
   */
  public function getImageAlt(): ?string {
    return $this->getValue('image_alt');
  }

  /**
   * Set Image Alt.
   */
  public function setImageAlt(string $imageAlt): self {
    return $this->setValue('image_alt', $imageAlt);
  }

  /**
   * Get Image Title.
   */
  public function getImageTitle(): ?string {
    return $this->getValue('image_title');
  }

  /**
   * Set Image Title.
   */
  public function setImageTitle(string $imageTitle): self {
    return $this->setValue('image_title', $imageTitle);
  }

  /**
   * Get Image Link.
   */
  public function getImageLink(): ?string {
    return $this->getValue('image_link');
  }

  /**
   * Set Image Link.
   */
  public function setImageLink(string $imageLink): self {
    return $this->setValue('image_link', $imageLink);
  }

  /**
   * Get Caption Title.
   */
  public function getCaptionTitle(): ?string {
    return $this->getValue('caption_title');
  }

  /**
   * Set Caption Title.
   */
  public function setCaptionTitle(string $captionTitle): self {
    return $this->setValue('caption_title', $captionTitle);
  }

  /**
   * Get Caption Text.
   */
  public function getCaptionText(): ?string {
    return $this->getValue('caption_text');
  }

  /**
   * Set Caption Text.
   */
  public function setCaptionText(string $captionText): self {
    return $this->setValue('caption_text', $captionText);
  }

  /**
   * Get Weight.
   */
  public function getWeight(): ?int {
    return $this->getValue('weight') ? (int) $this->getValue('weight') : NULL;
  }

  /**
   * Set Weight.
   */
  public function setWeight(int $weight): self {
    return $this->setValue('weight', $weight);
  }

  /**
   * Get Status.
   */
  public function getStatus(): ?int {
    return $this->getValue('status') ? (int) $this->getValue('status') : NULL;
  }

  /**
   * Set Status.
   */
  public function setStatus(int $status): self {
    return $this->setValue('status', $status);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['image_id'] = BaseFieldDefinition::create('integer')->setLabel(t('Image id'))->setDescription(t('The id of image file.'))->setSetting('unsigned', TRUE);
    $fields['image_alt'] = BaseFieldDefinition::create('string')->setLabel(t('Image Alt'))->setDescription(t('The alt of image.'))->setSetting('max_length', 255);
    $fields['image_title'] = BaseFieldDefinition::create('string')->setLabel(t('Image Title'))->setDescription(t('The title of image.'))->setSetting('max_length', 255);
    $fields['image_link'] = BaseFieldDefinition::create('string')->setLabel(t('Image Link'))->setDescription(t('The link of image.'))->setSetting('max_length', 255);
    $fields['caption_title'] = BaseFieldDefinition::create('string')->setLabel(t('Caption Title'))->setDescription(t('The title of caption.'))->setSetting('max_length', 100);
    $fields['caption_text'] = BaseFieldDefinition::create('string')->setLabel(t('Caption Text'))->setDescription(t('The text of caption.'))->setSetting('max_length', 255);
    $fields['weight'] = BaseFieldDefinition::create('integer')->setLabel(t('Weight'))->setDescription(t('The weight of the item.'))->setSetting('unsigned', TRUE);
    $fields['status'] = BaseFieldDefinition::create('integer')->setLabel(t('Status'))->setDescription(t('The status of item.'))->setSetting('unsigned', TRUE);
    return $fields;
  }

}
