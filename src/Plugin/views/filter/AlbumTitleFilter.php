<?php

namespace Drupal\media_album_light_table_style\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter by album title with a pre-populated select list.
 *
 * @ViewsFilter("album_title_filter")
 */
class AlbumTitleFilter extends InOperator {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['node_bundle'] = ['default' => 'media_album_av'];
    $options['load_on_demand'] = ['default' => TRUE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // Récupérer tous les types de contenu disponibles.
    $bundles = \Drupal::service('entity_type.bundle.info')
      ->getBundleInfo('node');
    $bundle_options = [];
    foreach ($bundles as $bundle_id => $bundle_info) {
      $bundle_options[$bundle_id] = $bundle_info['label'];
    }

    $form['node_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Content type to filter'),
      '#options' => $bundle_options,
      '#default_value' => $this->options['node_bundle'] ?? 'media_album_av',
      '#description' => $this->t('Select the content type whose titles will populate the filter list.'),
    ];

    $form['load_on_demand'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('No selection by default (load on demand)'),
      '#default_value' => $this->options['load_on_demand'] ?? TRUE,
      '#description' => $this->t('If checked, no results are shown until the user selects an album. Improves initial load performance.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);
    $this->options['node_bundle'] = $form_state->getValue(
      ['options', 'node_bundle']
    );
    $this->options['load_on_demand'] = $form_state->getValue(
      ['options', 'load_on_demand']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    $bundle = $this->options['node_bundle'] ?? 'media_album_av';

    $query = \Drupal::entityQuery('node')
      ->condition('type', $bundle)
      ->condition('status', 1)
      ->sort('title', 'ASC')
      ->accessCheck(TRUE);

    $nids = $query->execute();

    $this->valueOptions = [];

    if (empty($nids)) {
      return $this->valueOptions;
    }

    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadMultiple($nids);

    foreach ($nodes as $nid => $node) {
      $this->valueOptions[$nid] = $node->label();
    }

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    $load_on_demand = $this->options['load_on_demand'] ?? TRUE;

    $rc = parent::acceptExposedInput($input);

    // Si load_on_demand actif et aucune valeur sélectionnée,
    // forcer l'acceptation du filtre pour que query() soit appelé.
    if ($load_on_demand) {
      $identifier = $this->options['expose']['identifier'];
      $value = $input[$identifier] ?? [];

      if (empty($value) || $value === ['All'] || $value === 'All') {
        // Forcer value à vide ET forcer query() à être appelé.
        $this->value = [];
        return TRUE;
      }
    }

    return $rc;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);

    $load_on_demand = $this->options['load_on_demand'] ?? TRUE;

    if ($load_on_demand) {
      $identifier = $this->options['expose']['identifier'];

      if (isset($form[$identifier])) {
        // Remplacer le label "- Any -" par "- None -".
        if (isset($form[$identifier]['#options']['All'])) {
          $form[$identifier]['#options']['All'] = $this->t('- None -');
        }
        // Pour les widgets BEF (select2, checkboxes, etc.).
        if (isset($form[$identifier]['#empty_option'])) {
          $form[$identifier]['#empty_option'] = $this->t('- None -');
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $load_on_demand = $this->options['load_on_demand'] ?? TRUE;

    if ($load_on_demand && empty($this->value)) {
      $this->ensureMyTable();
      // Condition impossible — retourne 0 résultats.
      $this->query->addWhere(
      $this->options['group'],
      "$this->tableAlias.nid",
      -1,
      '='
      );
      return;
    }

    if (empty($this->value)) {
      return;
    }

    $this->ensureMyTable();
    $this->query->addWhere(
    $this->options['group'],
    "$this->tableAlias.nid",
    array_values($this->value),
    'IN'
    );
  }

}
