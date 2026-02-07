<?php

namespace Drupal\media_album_light_table_style\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\Core\Form\FormState;
use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\media_album_light_table_style\Service\MediaActionService;
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
      'Drupal\media_album_light_table_style\Form\MediaLightTableActionsForm',
      $form_state
    );

    return $form;
  }

}
