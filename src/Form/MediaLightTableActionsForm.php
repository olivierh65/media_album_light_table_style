<?php

namespace Drupal\media_album_light_table_style\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\SettingsCommand;

/**
 * Form for selecting and executing media light table actions.
 */
class MediaLightTableActionsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_album_light_table_actions_form' . $this->getRequest()->get('album_grp');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $album_grp = NULL, array $available_actions = [], int $use_actions = 0) {

    /* if (empty($available_actions)) {
    return $form;
    } */

    $form['#attributes']['class'][] = 'media-light-table-group-commandes';
    $form['#attributes']['data-album-grp'] = $album_grp;

    $form['#id'] = 'media-light-table-actions-form-' . $album_grp;

    $options = [];
    foreach ($available_actions as $action_id => $action) {
      $options[$action_id] = $action['label'];
    }

    // 1. La DIV enveloppe : .media-light-table-group-info-action
    $form['info_action_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['draggable-flexgrid__group-info-action', 'media-light-table-group-info-action'],
      ],
    ];

    $form['info_action_wrapper']['selected_items_count'] = [
      '#type' => 'hidden',
      '#default_value' => 0,
      '#attributes' => [
        'id' => 'selected-items-count-' . $album_grp,
      ],
    ];

    // 2. La DIV de gauche : .media-light-table-group-info
    $form['info_action_wrapper']['group_info'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['draggable-flexgrid__group-info', 'media-light-table-group-info'],
        'id' => 'info-action-wrapper-' . $album_grp,
      ],
    ];

    // On déplace votre counter_wrapper existant dans cette nouvelle DIV.
    $form['info_action_wrapper']['group_info']['counter_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['media-light-table-group-counter-wrapper'],
    // Correction: style est une chaîne, pas un tableau.
        'style' => 'display: none',
      ],
    ];

    $form['info_action_wrapper']['group_info']['counter_wrapper']['counter'] = [
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => [
        'class' => ['media-light-table-group-selection-counter'],
      ],
    ];

    $form['info_action_wrapper']['group_info']['counter_wrapper']['save_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sauvegarder l\'ordre'),
       // To identify the button in the ajax prepare function.
      // '#name' => 'save-button-' . $album_grp,.
      '#id' => 'save-button-' . $album_grp,
      // '#limit_validation_errors' => [],
      // '#submit' => [],
      '#attributes' => [
        'class' => ['media-light-table-save-button', 'media-light-table-ajax-button', 'js-media-save-reorg', 'button', 'js-form-submit', 'form-submit'],
        'data-album-grp' => $album_grp,
        'data-unique-key' => 'save_' . $album_grp,
        'data-prepare-function' => 'prepareReorgData',
      ],
      '#ajax' => [
        'callback' => '::saveAlbumReorganization',
        'wrapper' => 'info-action-wrapper-' . $album_grp,
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Saving...'),
        ],
        /* 'trigger_as' => ['name' => 'save-button-' . $album_grp], */
      ],
    ];

    $form['info_action_wrapper']['group_info']['counter_wrapper']['reorg_data'] = [
      '#type' => 'value',
      '#attributes' => [
        'id' => 'reorg-data-' . $album_grp,
      ],
    ];

    if ($use_actions == 1) {

      $form['info_action_wrapper']['group_action'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['draggable-flexgrid__group-action', 'media-light-table-group-action'],
          'id' => 'group-action-wrapper-' . $album_grp,
        ],
      ];

      // On déplace votre actions_toolbar existant ici.
      $form['info_action_wrapper']['group_action']['actions_toolbar'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['media-light-table-actions-toolbar'],
        ],
      ];

      $form['info_action_wrapper']['group_action']['actions_toolbar']['action_id'] = [
        '#type' => 'select',
        '#options' => ['none' => '- Select action -'] + $options,
        '#attributes' => [
          'id' => 'media-light-table-action-select-' . $album_grp,
        ],
      ];

      $form['info_action_wrapper']['group_action']['actions_toolbar']['execute'] = [
        '#type' => 'link',
        '#title' => $this->t('Execute'),
        '#url' => Url::fromRoute('media_album_av_common.action_form', [
          'action_id' => '__ACTION__',
          'album_grp' => $album_grp,
        ]),
        '#id' => 'execute-button-' . $album_grp,
        '#attributes' => [
          'class' => [
            'media-light-table-execute-action',
            'button',
            'js-form-submit',
          ],
          'data-album-grp' => $album_grp,
          'data-unique-key' => 'execute_' . $album_grp,
          'data-prepare-function' => 'prepareActionData',
        ],
        '#ajax' => [
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Loading...'),
          ],
        ],
        '#states' => [
          'disabled' => [
            '#media-light-table-action-select-' . $album_grp => ['value' => 'none'],
          ],
        ],
        '#attached' => [
          'library' => ['core/drupal.dialog.ajax'],
        ],
      ];
    }
    $form['#attached']['library'] = [
      'media_album_light_table_style/media-light-table',
    ];

    // This container wil be replaced by AJAX.
    $form['container'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'box-container'],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      // The AJAX handler will call our callback, and will replace whatever page
      // element has id box-container.
      '#ajax' => [
        'callback' => '::promptCallback',
        'wrapper' => 'box-container',
      ],
      '#value' => $this->t('Submit'),
    ];

    $form['submit2'] = [
      '#type' => 'submit',
      // The AJAX handler will call our callback, and will replace whatever page
      // element has id box-container.
      '#ajax' => [
        'callback' => '::callbackExecuteAction',
        'wrapper' => 'box-container',
      ],
      '#value' => $this->t('Submit2'),
    ];

    return $form;
  }

  /**
   *
   */
  public function mySubmitHandler(array &$form, FormStateInterface $form_state) {
    // This is handled by AJAX callback.
    $triggering_element = $form_state->getTriggeringElement();
  }

  /**
   * Callback for submit_driven example.
   *
   * Select the 'box' element, change the markup in it, and return it as a
   * renderable array.
   *
   * @return array
   *   Renderable array (the box element)
   */
  public function promptCallback(array &$form, FormStateInterface $form_state) {
    // In most cases, it is recommended that you put this logic in form
    // generation rather than the callback. Submit driven forms are an
    // exception, because you may not want to return the form at all.
    $element = $form['container'];
    $element['box']['#markup'] = "Clicked submit ({$form_state->getValue('op')}): " . date('c');
    return $element;
  }

  /**
   *
   */
  public function promptCallback2(array &$form, FormStateInterface $form_state) {
    // In most cases, it is recommended that you put this logic in form
    // generation rather than the callback. Submit driven forms are an
    // exception, because you may not want to return the form at all.
    $element = $form['container'];
    $element['box']['#markup'] = "Clicked submit ({$form_state->getValue('op')}): " . date('c');
    return $element;
  }

  /**
   *
   */
  public static function optionsFormEntitySourceSubmitAjax(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $array_parents = $triggering_element['#array_parents'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This is handled by AJAX callback.
  }

  /**
   *
   */
  public function saveAlbumReorganization(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    $data = $form_state->getUserInput()['prepared_media_data'];
    $data = json_decode($data, TRUE);

    $media_order = $data['media_order'] ?? NULL;

    if (!empty($media_order) || (($data['action']) ?? '') === 'reorg') {
      $mediaOrderService = \Drupal::service('media_album_av_common.media_order_service');
      $result = $mediaOrderService->saveMediaOrder($media_order);
      $album_grp = $media_order[0]['album_grp'] ?? NULL;

      // Afficher le message approprié en fonction du résultat.
      if ($result['success']) {
        $message = $this->t('Sauvegarde réussie ! (@count items processed)',
          ['@count' => $result['processed']]
        );
        $response->addCommand(new MessageCommand($message, 'status'));
      }
      else {
        $message = $this->t('Erreur lors de la sauvegarde : @message',
          ['@message' => $result['message']]
        );
        $response->addCommand(new MessageCommand($message, 'error'));

        // Ajouter les erreurs détaillées si présentes.
        if (!empty($result['errors'])) {
          foreach ($result['errors'] as $error) {
            $response->addCommand(new MessageCommand($error, 'warning'));
          }
        }
      }

      // Vous pouvez aussi envoyer une commande JS personnalisée pour reset le state 'hasChanges'.
      $response->addCommand(new InvokeCommand('.media-light-table-save-button',
        'removeClass', ['is-loading']));

      $response->addCommand(new SettingsCommand([
        'mediaReorg' => [
          'albumGrp' => $album_grp,
          'result' => $result,
        ],
      ], TRUE));
      // Déclencher un événement personnalisé jQuery qui appellera handleReorgAjaxResponse.
      $response->addCommand(new InvokeCommand('.js-media-save-reorg[data-album-grp="' . $album_grp . '"]', 'trigger', ['reorgAjaxResponse']));
    }
    else {
      $response->addCommand(new MessageCommand($this->t('Aucune donnée à sauvegarder'), 'warning'));
    }

    return $response;
  }

  /**
   * AJAX callback to execute the action.
   */
  public function callbackExecuteAction(array &$form, FormStateInterface $form_state) {

    $response = new AjaxResponse();
    $data = $form_state->getUserInput()['prepared_media_data'];
    $data = json_decode($data, TRUE);

    if (empty($data) || !isset($data['action'])) {
      $response->addCommand(new MessageCommand('Action not specified', 'warning'));
      return $response;
    }

    $action_manager = \Drupal::service('plugin.manager.action');
    $action = $action_manager->createInstance($data['action'], $data['selected_items']);
    $ret = $action->execute();

    $response->addCommand(new MessageCommand('Action executed', 'info'));

    return $response;
  }

}
