<?php

namespace Drupal\media_album_light_table_style\Service;

use Drupal\Core\Action\ActionPluginManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Service for managing media actions in light table views.
 */
class MediaActionService {

  /**
   * The action plugin manager.
   *
   * @var \Drupal\Core\Action\ActionManager|\Drupal\Core\Action\ActionPluginManager
   */
  protected $actionPluginManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a MediaActionService object.
   */
  public function __construct(
    $action_plugin_manager,
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user
  ) {
    $this->actionPluginManager = $action_plugin_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * Get all available actions for media entities.
   *
   * @return array
   *   Array of action definitions keyed by action ID.
   */
  public function getAvailableActions() {
    $actions = [];

    // Get all action plugins.
    $all_actions = $this->actionPluginManager->getDefinitions();

    foreach ($all_actions as $action_id => $definition) {
      // Filter for media actions only.
      if (isset($definition['type']) && $definition['type'] === 'media') {
        // Check if user has access to this action.
        try {
          $action = $this->actionPluginManager->createInstance($action_id);

          // Only include configureable actions that have a modal/form.
          if ($action instanceof \Drupal\Core\Action\ConfigurableActionBase) {
            $actions[$action_id] = [
              'id' => $action_id,
              'label' => $definition['label'],
              'category' => $definition['category'] ?? 'Other',
              'description' => $definition['description'] ?? '',
              'configureable' => !empty($definition['confirm']),
            ];
          }
        }
        catch (\Exception $e) {
          // Skip actions that can't be instantiated.
          continue;
        }
      }
    }

    return $actions;
  }

  /**
   * Execute an action on multiple media entities.
   *
   * @param string $action_id
   *   The action plugin ID.
   * @param array $media_ids
   *   Array of media entity IDs.
   * @param array $configuration
   *   Action configuration (if any).
   *
   * @return array
   *   Result array with status and messages.
   */
  public function executeActionOnMedia($action_id, array $media_ids, array $configuration = []) {
    $result = [
      'success' => FALSE,
      'message' => '',
      'executed_count' => 0,
    ];

    if (empty($media_ids)) {
      $result['message'] = 'No media selected.';
      return $result;
    }

    try {
      $action = $this->actionPluginManager->createInstance($action_id, $configuration);

      // Load media entities.
      $media_storage = $this->entityTypeManager->getStorage('media');
      $media_entities = $media_storage->loadMultiple($media_ids);

      $executed_count = 0;
      foreach ($media_entities as $media) {
        try {
          $action->execute($media);
          $executed_count++;
        }
        catch (\Exception $e) {
          \Drupal::logger('media_album_light_table_style')->error(
            'Error executing action @action on media @media: @message',
            [
              '@action' => $action_id,
              '@media' => $media->id(),
              '@message' => $e->getMessage(),
            ]
          );
        }
      }

      $result['success'] = TRUE;
      $result['executed_count'] = $executed_count;
      $result['message'] = "Action executed on $executed_count media.";
    }
    catch (\Exception $e) {
      $result['message'] = 'Error executing action: ' . $e->getMessage();
    }

    return $result;
  }

}
