<?php

declare(strict_types=1);

namespace Drupal\graphql_compose_views\Plugin\views\row;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Plugin which displays fields as raw data.
 *
 * @ViewsRow(
 *   id = "graphql_field",
 *   title = @Translation("Fields"),
 *   help = @Translation("Use fields as row data."),
 *   display_types = {"graphql"},
 * )
 */
class GraphQLFieldRow extends RowPluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * Stores an array of prepared field aliases from options.
   *
   * @var array
   */
  protected $replacementAliases = [];

  /**
   * Stores an array of options to determine if the raw field output is used.
   *
   * @var array
   */
  protected $rawOutputOptions = [];

  /**
   * Stores an array of field GraphQL type.
   *
   * @var array
   */
  protected $typeOptions = [];

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL) {
    parent::init($view, $display, $options);

    if (!empty($this->options['field_options'])) {
      $options = (array) $this->options['field_options'];
      // Prepare a trimmed version of replacement aliases.
      $aliases = static::extractFromOptionsArray('alias', $options);
      $this->replacementAliases = array_filter(array_map('trim', $aliases));
      // Prepare an array of raw output field options.
      $this->rawOutputOptions = static::extractFromOptionsArray('raw_output', $options);
      $this->typeOptions = static::extractFromOptionsArray('type', $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['field_options'] = ['default' => []];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['field_options'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Field'),
        $this->t('Alias'),
        $this->t('Raw output'),
        $this->t('Type'),
      ],
      '#empty' => $this->t('You have no fields. Add some to your view.'),
      '#tree' => TRUE,
    ];

    $options = $this->options['field_options'];

    if ($fields = $this->view->display_handler->getOption('fields')) {
      foreach ($fields as $id => $field) {
        // Don't show the field if it has been excluded.
        if (!empty($field['exclude'])) {
          continue;
        }

        $form['field_options'][$id]['field'] = [
          '#markup' => $id,
        ];

        $form['field_options'][$id]['alias'] = [
          '#title' => $this->t('Alias for @id', ['@id' => $id]),
          '#title_display' => 'invisible',
          '#type' => 'textfield',
          '#default_value' => $options[$id]['alias'] ?? '',
          '#element_validate' => [[$this, 'validateAliasName']],
        ];

        $form['field_options'][$id]['raw_output'] = [
          '#title' => $this->t('Raw output for @id', ['@id' => $id]),
          '#title_display' => 'invisible',
          '#type' => 'checkbox',
          '#default_value' => $options[$id]['raw_output'] ?? '',
        ];

        $form['field_options'][$id]['type'] = [
          '#type' => 'select',
          '#options' => [
            'String' => $this->t('String'),
            'Int' => $this->t('Int'),
            'Float' => $this->t('Float'),
            'Boolean' => $this->t('Boolean'),
            'Scalar' => $this->t('Custom Scalar'),
          ],
          '#default_value' => $options[$id]['type'] ?? 'String',
        ];
      }
    }
  }

  /**
   * Form element validation handler.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateAliasName(array $element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if ($value && !preg_match('/^[a-z]([A-Za-z0-9]+)?$/', $value)) {
      $message = $this->t('@name must start with a lowercase letter and contain only letters and numbers.', [
        '@name' => $element['#title'] ?? 'Field name',
      ]);
      $form_state->setError($element, $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    // Collect an array of aliases to validate.
    $aliases = static::extractFromOptionsArray(
      'alias',
      $form_state->getValue(['row_options', 'field_options'])
    );

    // If array filter returns empty, no values have been entered. Unique keys
    // should only be validated if we have some.
    if (($filtered = array_filter($aliases)) && (array_unique($filtered) !== $filtered)) {
      $form_state->setErrorByName('aliases', $this->t('All field aliases must be unique'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    $output = [];

    foreach ($this->view->field as $id => $field) {

      // Omit excluded fields from the rendered output.
      if (!empty($field->options['exclude'])) {
        continue;
      }

      // If the raw output option has been set, just get the raw value.
      if (!empty($this->rawOutputOptions[$id])) {
        $value = $field->getValue($row);
      }
      // Otherwise, pass this through the field advancedRender() method.
      else {
        // Advanced render for token replacement.
        $markup = $field->advancedRender($row);
        // Post render to support un-cacheable fields.
        $field->postRender($row, $markup);
        $value = $field->last_render;
      }

      // Convert markup and filter XSS.
      $value = $value instanceof MarkupInterface ? $value->__toString() : $value;
      $value = is_string($value) ? Xss::filter($value) : $value;

      // Cleanup non-string values for type conversion.
      if ($this->getFieldType($id) !== 'String') {
        $value = is_string($value) ? strip_tags($value) : $value;
        $value = is_string($value) ? trim($value) : $value;
      }

      if ($this->getFieldType($id) === 'Int') {
        $value = (int) $value;
      }

      if ($this->getFieldType($id) === 'Boolean') {
        $truthy = [
          $this->t('yes')->__toString(),
          $this->t('true')->__toString(),
          $this->t('on')->__toString(),
          $this->t('enabled')->__toString(),
          'yes',
          'true',
          'on',
          'enabled',
          '1',
          1,
          TRUE,
        ];

        $value = is_string($value) ? strtolower($value) : $value;
        $value = in_array($value, $truthy, TRUE);
      }

      if ($this->getFieldType($id) === 'Float') {
        $value = (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
      }

      $output[$this->getFieldKeyAlias($id)] = $value;
    }

    return $output;
  }

  /**
   * Return an alias for a field ID, as set in the options form.
   *
   * @param string $id
   *   The field id to lookup an alias for.
   *
   * @return string
   *   The matches user entered alias, or the original ID if nothing is found.
   */
  public function getFieldKeyAlias($id) {
    return $this->replacementAliases[$id] ?? $id;
  }

  /**
   * Return a GraphQL field type, as set in the options form.
   *
   * @param string $id
   *   The field id to lookup a type for.
   *
   * @return string
   *   The matches user entered type, or String.
   */
  public function getFieldType($id) {
    return $this->typeOptions[$id] ?? 'String';
  }

  /**
   * Extracts a set of option values from a nested options array.
   *
   * @param string $key
   *   The key to extract from each array item.
   * @param array $options
   *   The options array to return values from.
   *
   * @return array
   *   A regular one dimensional array of values.
   */
  protected static function extractFromOptionsArray($key, array $options) {
    return array_map(function ($item) use ($key) {
      return $item[$key] ?? NULL;
    }, $options);
  }

}
