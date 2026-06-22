<?php

namespace Drupal\media_album_light_table_style\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Form\FormState;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\media_album_light_table_style\Service\MediaActionService;
use Drupal\media_album_light_table_style\Form\MediaLightTableActionsForm;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for media light table actions.
 */
class MediaActionController extends ControllerBase {

  /**
   * The media action service.
   *
   * @var \Drupal\media_album_light_table_style\Service\MediaActionService
   */
  protected $mediaActionService;

  /**
   * The action plugin manager.
   *
   * @var \Drupal\Core\Action\ActionManager|\Drupal\Core\Action\ActionPluginManager
   */
  protected $actionPluginManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a MediaActionController object.
   */
  public function __construct(
    MediaActionService $media_action_service,
    $action_plugin_manager,
    FormBuilderInterface $form_builder,
    RendererInterface $renderer,
  ) {
    $this->mediaActionService = $media_action_service;
    $this->actionPluginManager = $action_plugin_manager;
    $this->formBuilder = $form_builder;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('media_album_light_table_style.media_action'),
      $container->get('plugin.manager.action'),
      $container->get('form_builder'),
      $container->get('renderer')
    );
  }

  /**
   * Returns media file info (URL, MIME type, dimensions) for a given media ID.
   *
   * Called by the JS zoom callback to avoid embedding the full URL in the HTML.
   *
   * @param int $media_id
   *   The media entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON with keys: url, alt, mime_type, media_type, width, height.
   */
  public function getMediaInfo(int $media_id) {
    if ($this->currentUser()->isAnonymous()) {
      return new JsonResponse(['error' => 'Access denied.'], 403);
    }

    /** @var \Drupal\media\MediaInterface|null $media */
    $media = $this->entityTypeManager()->getStorage('media')->load($media_id);

    if (!$media || !$media->access('view', $this->currentUser())) {
      return new JsonResponse(['error' => 'Media not found or access denied.'], 404);
    }

    try {
      $source_field_def = $media->getSource()->getSourceFieldDefinition($media->bundle->entity);
      $field_name = $source_field_def->getName();

      if (!$media->hasField($field_name) || $media->get($field_name)->isEmpty()) {
        return new JsonResponse(['error' => 'No source file on this media.'], 404);
      }

      $file = $media->get($field_name)->entity;

      if (!$file) {
        return new JsonResponse(['error' => 'File entity not found.'], 404);
      }

      $file_uri = $file->getFileUri();
      /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
      $file_url_generator = \Drupal::service('file_url_generator');
      $url = $file_url_generator->generateAbsoluteString($file_uri);
      $mime_type = $file->getMimeType();

      $width = 0;
      $height = 0;
      $real_path = \Drupal::service('file_system')->realpath($file_uri);
      if ($real_path && file_exists($real_path)) {
        $image_info = @getimagesize($real_path);
        if (is_array($image_info)) {
          $width = $image_info[0];
          $height = $image_info[1];
        }
      }

      $alt = '';
      if ($media->hasField('field_media_image') && !$media->get('field_media_image')->isEmpty()) {
        $alt = $media->get('field_media_image')->first()->alt ?? '';
      }
      if (empty($alt)) {
        $alt = $media->label();
      }

      $custom_fields = $this->extractCustomMediaFields($media);

      return new JsonResponse([
        'url'        => $url,
        'alt'        => $alt,
        'mime_type'  => $mime_type,
        'media_type' => explode('/', $mime_type)[0] ?? 'image',
        'width'      => $width,
        'height'     => $height,
        // Données pour le popup "More…"
        'id'         => $media->id(),
        'label'      => $media->label(),
        'file_name'  => $file->getFilename(),
        'file_path'  => $real_path ?: '',
        'size_bytes' => $file->getSize(),
        'bundle'     => $media->bundle(),
        'custom_fields' => $custom_fields,
      ]);
    }
    catch (\Exception $e) {
      \Drupal::logger('media_album_light_table_style')->error('getMediaInfo error for media @id: @msg', [
        '@id'  => $media_id,
        '@msg' => $e->getMessage(),
      ]);
      return new JsonResponse(['error' => 'Server error.'], 500);
    }
  }

  /**
   * Returns all non-system (configured) fields with normalized values.
   *
   * @param \Drupal\media\MediaInterface $media
   *   The media entity.
   *
   * @return array
   *   Keyed by machine name: ['label' => string, 'value' => string].
   */
  protected function extractCustomMediaFields($media): array {
    $result = [];

    foreach ($media->getFieldDefinitions() as $field_name => $field_definition) {
      // Keep only configured (non base/system) fields.
      if ($field_definition->getFieldStorageDefinition()->isBaseField()) {
        continue;
      }

      if (!$media->hasField($field_name) || $media->get($field_name)->isEmpty()) {
        continue;
      }

      $normalized_value = $this->normalizeFieldItemList($media->get($field_name), $field_definition->getType());
      if ($normalized_value === NULL || $normalized_value === '') {
        continue;
      }

      $result[$field_name] = [
        'label' => (string) $field_definition->getLabel(),
        'value' => $normalized_value,
      ];
    }

    return $result;
  }

  /**
   * Normalizes a field item list to a human readable string.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Field items.
   * @param string $field_type
   *   Field type.
   *
   * @return string|null
   *   Normalized value.
   */
  protected function normalizeFieldItemList($items, string $field_type): ?string {
    switch ($field_type) {
      case 'entity_reference':
        $labels = [];
        foreach ($items->referencedEntities() as $entity) {
          $labels[] = $entity->label();
        }

        if (!empty($labels)) {
          return implode(', ', $labels);
        }

        $ids = [];
        foreach ($items as $item) {
          if (!empty($item->target_id)) {
            $ids[] = (string) $item->target_id;
          }
        }
        return !empty($ids) ? implode(', ', $ids) : NULL;

      case 'boolean':
        $item = $items->first();
        if (!$item) {
          return NULL;
        }
        return !empty($item->value) ? (string) $this->t('Yes') : (string) $this->t('No');

      case 'link':
        $values = [];
        foreach ($items as $item) {
          $uri = $item->uri ?? '';
          $title = $item->title ?? '';
          if ($uri !== '') {
            $values[] = $title !== '' ? $title . ' (' . $uri . ')' : $uri;
          }
        }
        return !empty($values) ? implode(', ', $values) : NULL;

      default:
        $values = [];
        $main_property = $items->getFieldDefinition()->getFieldStorageDefinition()->getMainPropertyName() ?: 'value';

        foreach ($items as $item) {
          $value = $item->{$main_property} ?? NULL;
          if (is_scalar($value) && $value !== '') {
            $values[] = (string) $value;
            continue;
          }

          $item_array = $item->toArray();
          foreach ($item_array as $candidate) {
            if (is_scalar($candidate) && $candidate !== '') {
              $values[] = (string) $candidate;
              break;
            }
          }
        }

        return !empty($values) ? implode(', ', $values) : NULL;
    }
  }

  /**
   * Execute action on selected media.
   */
  public function executeAction(Request $request) {
    // Check if user is authenticated.
    if ($this->currentUser()->isAnonymous()) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'You must be logged in to execute actions',
      ], 403);
    }

    $action_id = $request->request->get('action_id');
    $media_ids = $request->request->get('media_ids', []);
    $configuration = $request->request->get('configuration', []);

    if (empty($action_id) || empty($media_ids)) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Invalid action or media selection',
      ]);
    }

    // Execute action.
    $result = $this->mediaActionService->executeActionOnMedia($action_id, $media_ids, $configuration);

    return new JsonResponse($result);
  }

  /**
   * Get action configuration form.
   */
  public function getActionConfig($action_id, Request $request) {
    // Check if user is authenticated.
    if ($this->currentUser()->isAnonymous()) {
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'You must be logged in',
      ], 403);
    }

    // Récupérer le contenu JSON envoyé par le JS.
    $content = $request->getContent();
    $params = json_decode($content, TRUE);
    $media_ids = $params['media_ids'] ?? [];
    // Log pour debug.
    \Drupal::logger('media_album_light_table_style')->notice('showActionDialog received: content=@content, params=@params, media_ids=@ids', [
      '@content' => $content,
      '@params' => json_encode($params),
      '@ids' => json_encode($media_ids),
    ]);
    try {
      $action = $this->actionPluginManager->createInstance($action_id);

      // Check if action implements ConfigurableActionInterface.
      if ($action instanceof ConfigurableInterface) {
        // Créer un FormState avec les media_ids.
        $form_state = new FormState();
        // Force fresh form - don't use previous configuration.
        $form_state->set('selected_media_ids', $media_ids);
        $form_state->set('skip_configuration', TRUE);
        // Construire le formulaire.
        $form = [];
        $form = $action->buildConfigurationForm($form, $form_state);

        // Important: ajouter les librairies AJAX et form.
        if (!isset($form['#attached'])) {
          $form['#attached'] = [];
        }
        if (!isset($form['#attached']['library'])) {
          $form['#attached']['library'] = [];
        }

        $form['#attached']['library'][] = 'core/drupal.ajax';
        $form['#attached']['library'][] = 'core/drupal.form';
        $form['#attached']['library'][] = 'core/drupal.form-states';
        $form['#attached']['library'][] = 'core/jquery.form';

        // Rendre le formulaire avec tous les attachements.
        $html = $this->renderer->render($form);

        return new JsonResponse([
          'html' => $html,
          'success' => TRUE,
        ]);
      }
      else {
        // Action does not need configuration.
        return new JsonResponse([
          'success' => FALSE,
          'message' => 'This action does not require configuration',
        ]);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('media_album_light_table_style')->error('Error loading action configuration for @action_id: @error', [
        '@action_id' => $action_id,
        '@error' => $e->getMessage(),
      ]);
      return new JsonResponse([
        'success' => FALSE,
        'message' => 'Action configuration not available',
      ]);
    }
  }

  /**
   * Show action configuration as AJAX dialog.
   */
  public function showActionDialog($action_id, Request $request) {
    // Check if user is authenticated.
    if ($this->currentUser()->isAnonymous()) {
      return new AjaxResponse();
    }

    // Récupérer le contenu JSON envoyé par le JS.
    $content = $request->getContent();
    $params = json_decode($content, TRUE);
    $media_ids = $params['media_ids'] ?? [];

    try {
      $action = $this->actionPluginManager->createInstance($action_id);

      // Check if action implements ConfigurableActionInterface.
      if ($action instanceof ConfigurableInterface) {
        // Créer un FormState avec les media_ids.
        $form_state = new FormState();
        $form_state->set('selected_media_ids', $media_ids);
        // $form_state->set('skip_configuration', TRUE);
        // Construire le formulaire.
        $form = [];
        $form = $action->buildConfigurationForm($form, $form_state);

        // Rendre le formulaire.
        $html = $this->renderer->render($form);

        // Créer une réponse AJAX avec OpenDialogCommand.
        $response = new AjaxResponse();
        $response->addCommand(new OpenDialogCommand(
          '#media-light-table-action-dialog',
          $action->getPluginDefinition()['label'],
          $html,
          [
            'width' => '90%',
            'modal' => TRUE,
          ]
        ));

        return $response;
      }
      else {
        // Action does not need configuration - execute directly.
        $response = new AjaxResponse();
        return $response;
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('media_album_light_table_style')->error('Error opening action dialog for @action_id: @error', [
        '@action_id' => $action_id,
        '@error' => $e->getMessage(),
      ]);
      return new AjaxResponse();
    }
  }

  /**
   * Render the actions toolbar form.
   */
  public function renderActionsToolbar($album_grp, Request $request) {
    // Check if user is authenticated.
    if ($this->currentUser()->isAnonymous()) {
      return ['#markup' => ''];
    }

    // Get available actions from request.
    $content = $request->getContent();
    $params = json_decode($content, TRUE);
    $available_actions = $params['available_actions'] ?? [];

    if (empty($available_actions)) {
      return ['#markup' => ''];
    }

    // Create form state with available actions.
    $form_state = new FormState();
    $form_state->set('available_actions', $available_actions);
    $form_state->set('album_grp', $album_grp);

    // Build and render the form.
    $form = $this->formBuilder()->getForm(
      MediaLightTableActionsForm::class,
      $form_state
    );

    return $form;
  }

}
