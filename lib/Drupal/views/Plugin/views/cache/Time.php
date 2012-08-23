<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\cache\Time.
 */

namespace Drupal\views\Plugin\views\cache;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Simple caching of query results for Views displays.
 *
 * @ingroup views_cache_plugins
 *
 * @Plugin(
 *   id = "time",
 *   title = @Translation("Time-based"),
 *   help = @Translation("Simple time-based caching of data."),
 *   help_topic = "cache-time"
 * )
 */
class Time extends CachePluginBase {

  /**
   * Overrides Drupal\views\Plugin\Plugin::$usesOptions.
   */
  protected $usesOptions = TRUE;

  function option_definition() {
    $options = parent::option_definition();
    $options['results_lifespan'] = array('default' => 3600);
    $options['results_lifespan_custom'] = array('default' => 0);
    $options['output_lifespan'] = array('default' => 3600);
    $options['output_lifespan_custom'] = array('default' => 0);

    return $options;
  }

  function options_form(&$form, &$form_state) {
    parent::options_form($form, $form_state);
    $options = array(60, 300, 1800, 3600, 21600, 518400);
    $options = drupal_map_assoc($options, 'format_interval');
    $options = array(-1 => t('Never cache')) + $options + array('custom' => t('Custom'));

    $form['results_lifespan'] = array(
      '#type' => 'select',
      '#title' => t('Query results'),
      '#description' => t('The length of time raw query results should be cached.'),
      '#options' => $options,
      '#default_value' => $this->options['results_lifespan'],
    );
    $form['results_lifespan_custom'] = array(
      '#type' => 'textfield',
      '#title' => t('Seconds'),
      '#size' => '25',
      '#maxlength' => '30',
      '#description' => t('Length of time in seconds raw query results should be cached.'),
      '#default_value' => $this->options['results_lifespan_custom'],
      '#states' => array(
        'visible' => array(
          ':input[name="cache_options[results_lifespan]"]' => array('value' => 'custom'),
        ),
      ),
    );
    $form['output_lifespan'] = array(
      '#type' => 'select',
      '#title' => t('Rendered output'),
      '#description' => t('The length of time rendered HTML output should be cached.'),
      '#options' => $options,
      '#default_value' => $this->options['output_lifespan'],
    );
    $form['output_lifespan_custom'] = array(
      '#type' => 'textfield',
      '#title' => t('Seconds'),
      '#size' => '25',
      '#maxlength' => '30',
      '#description' => t('Length of time in seconds rendered HTML output should be cached.'),
      '#default_value' => $this->options['output_lifespan_custom'],
      '#states' => array(
        'visible' => array(
          ':input[name="cache_options[output_lifespan]"]' => array('value' => 'custom'),
        ),
      ),
    );
  }

  function options_validate(&$form, &$form_state) {
    $custom_fields = array('output_lifespan', 'results_lifespan');
    foreach ($custom_fields as $field) {
      if ($form_state['values']['cache_options'][$field] == 'custom' && !is_numeric($form_state['values']['cache_options'][$field . '_custom'])) {
        form_error($form[$field .'_custom'], t('Custom time values must be numeric.'));
      }
    }
  }

  function summary_title() {
    $results_lifespan = $this->get_lifespan('results');
    $output_lifespan = $this->get_lifespan('output');
    return format_interval($results_lifespan, 1) . '/' . format_interval($output_lifespan, 1);
  }

  function get_lifespan($type) {
    $lifespan = $this->options[$type . '_lifespan'] == 'custom' ? $this->options[$type . '_lifespan_custom'] : $this->options[$type . '_lifespan'];
    return $lifespan;
  }

  function cache_expire($type) {
    $lifespan = $this->get_lifespan($type);
    if ($lifespan) {
      $cutoff = REQUEST_TIME - $lifespan;
      return $cutoff;
    }
    else {
      return FALSE;
    }
  }

  function cache_set_expire($type) {
    $lifespan = $this->get_lifespan($type);
    if ($lifespan) {
      return time() + $lifespan;
    }
    else {
      return CACHE_PERMANENT;
    }
  }

}
