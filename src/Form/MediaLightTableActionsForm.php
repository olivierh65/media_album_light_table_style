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
  public function buildForm(array $form, FormStateInterface $form_state, $album_grp = NULL, array $available_actions = [], int $use_actions = 0, int $use_save_reorg = 1) {

    /* if (empty($available_actions)) {
    return $form;
    } */

    $form['#attributes']['class'][] = 'media-light-table-group-commandes';
    $form['#attributes']['data-album-grp'] = $album_grp;

    $form['#id'] = 'media-light-table-actions-form-' . $album_grp;

    $options = [];
    foreach ($available_actions as $action_id => $action) {
      $options[$action_id . '|' . $action['prepare_js_function']] = $action['label'];
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

    $form['info_action_wrapper']['group_info']['counter_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['media-light-table-group-counter-wrapper'],
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

    $form['info_action_wrapper']['group_info']['counter_wrapper']['deselect_all'] = [
      '#type' => 'button',
      '#value' => $this->t('✕'),
      '#attributes' => [
        'class' => ['media-light-table-deselect-all', 'button'],
        'data-album-grp' => $album_grp,
        'title' => $this->t('Désélectionner tout'),
    // évite le submit.
        'type' => 'button',
      ],
    ];

    $form['info_action_wrapper']['group_info']['counter_wrapper']['save_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sauvegarder l\'album'),
     // To identify the button in the ajax prepare function.
    // '#name' => 'save-button-' . $album_grp,.
      '#id' => 'save-button-' . $album_grp,
    // '#limit_validation_errors' => [],
    // '#submit' => [],
      '#attributes' => [
        'class' => [
          'media-light-table-save-button',
          'media-light-table-ajax-button',
          'js-media-save-reorg',
          'button',
          'js-form-submit',
          'form-submit',
    // Masquer le bouton si la fonctionnalité est désactivée].
          $use_save_reorg != 0 ? '' : 'visually-hidden',
        ],
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
      '#type' => 'hidden',
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
          // 'data-prepare-function' => 'prepareActionData',
          // genere la base de l'URL sans les paramètres spécifiques à l'action, qui seront ajoutés dynamiquement en JS avant l'appel AJAX.
          'data-base-url' => str_replace(
            ['/__ACTION__', '/__ALBUM_GRP__'],
            '',
            Url::fromRoute('media_album_av_common.action_form', [
              'action_id' => '__ACTION__',
              'album_grp' => '__ALBUM_GRP__',
            ])->toString()
          ),
          'data-action-select-id' => 'media-light-table-action-select-' . $album_grp,
        ],
        '#ajax' => [
          'method' => 'POST',
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
      'media_album_light_table_style/media-light-table-actions',
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
   * AJAX callback to save album reorganization.
   */
  public function saveAlbumReorganization(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    try {
      // Get the prepared data from the form field.
      $reorg_data = $form_state->getValue('reorg_data');

      if (empty($reorg_data)) {
        $response->addCommand(new MessageCommand($this->t('Aucune donnée à sauvegarder'), 'warning'));
        return $response;
      }

      // If it's a JSON string, decode it; otherwise use as-is.
      if (is_string($reorg_data)) {
        $data = json_decode($reorg_data, TRUE);
      }
      else {
        $data = $reorg_data;
      }

      $media_order = $data['media_order'] ?? NULL;

      if (!empty($media_order) || (($data['action']) ?? '') === 'reorg') {
        $mediaOrderService = \Drupal::service('media_album_av_common.media_order_service');
        $result = $mediaOrderService->saveMediaOrder($data);
        $album_grp = $media_order[0]['album_grp'] ?? NULL;

        // Afficher le message approprié en fonction du résultat.
        if ($result['success']) {
          $message = $this->t('Sauvegarde réussie ! (@count items , @count_taxo taxonprocessed)',
            ['@count' => $result['processed_media'], '@count_taxo' => $result['processed_taxonomy']]
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
    }
    catch (\Exception $e) {
      $response->addCommand(new MessageCommand(
        $this->t('Erreur lors du traitement : @message', ['@message' => $e->getMessage()]),
        'error'
      ));
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
