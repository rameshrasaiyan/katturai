<?php
/**
 * @file
 * Contains the theme's functions to manipulate Drupal's default markup.
 */

/**
 * Implements THEME_preprocess_node().
 */
function katturai_preprocess_node(&$variables) {
  $node = $variables['node'];
  $variables['user_picture'] = FALSE;
  $variables['submitted'] = FALSE;
  $variables['content']['field_tags']['#title'] = FALSE;
  $variables['content']['links']['comment'] = FALSE;
  $variables['content']['field_tags']['#theme'] = 'links';
  unset($variables['content']['links']['node']['#links']['node-readmore']);
  $variables['newreadmore'] = l(t('Read More'), 'node/' . $node->nid);
  $variables['date_day'] = format_date($node->created, 'custom', 'j');
  $variables['date_month'] = format_date($node->created, 'custom', 'F');
  $variables['date_year'] = format_date($node->created, 'custom', 'Y');
}

/**
 * Implements THEME_status_messages().
 */
function katturai_status_messages($variables) {
  $display = $variables['display'];
  $output = '';

  $status_heading = array(
    'status' => t('Status message'),
    'error' => t('Error message'),
    'warning' => t('Warning message'),
  );
  foreach (drupal_get_messages($display) as $type => $messages) {
    $output .= "<div class=\"messages container $type\">\n";
    if (!empty($status_heading[$type])) {
      $output .= '<h2 class="element-invisible">' . $status_heading[$type] . "</h2>\n";
    }
    if (count($messages) > 1) {
      $output .= " <ul>\n";
      foreach ($messages as $message) {
        $output .= '  <li>' . $message . "</li>\n";
      }
      $output .= " </ul>\n";
    }
    else {
      $output .= $messages[0];
    }
    $output .= "</div>\n";
  }
  return $output;
}

/**
 * Implements template_preprocess_page().
 */
function katturai_preprocess_page(&$variables) {
  $variables['content']['links']['#links']['comment-reply']['#attributes']['class'] = array('btn btn-default');;
}

/**
 * Implements theme_item_list().
 */
function katturai_item_list($vars) {
  if (isset($vars['attributes']['class']) &&
    is_array($vars['attributes']['class']) &&
    in_array('pager', $vars['attributes']['class'])) {
    // Adjust pager output.
    $vars['attributes']['class'] = array('pagination pagination-sm');
    foreach ($vars['items'] as &$item) {
      if (in_array('pager-current', $item['class'])) {
        $item['class'] = array('active');
        $item['data'] = '<span>' . $item['data'] . '</span>';
      }
      elseif (in_array('pager-ellipsis', $item['class'])) {
        $item['class'] = array('disabled');
        $item['data'] = '<span>' . $item['data'] . '</span>';
      }
    }
    return '<div class="custom-pagination">' . theme_item_list($vars) . '</div>';
  }
  return theme_item_list($vars);
}

function katturai_form_alter(&$form, &$form_state, $form_id) {
  if ($form_id == 'search_block_form') {
    unset($form['search_block_form']['#theme_wrappers']);
    $form['actions']['submit']['#attributes']['class'] = array('btn btn-default');
    $form['search_block_form']['#attributes']['class'] = array('form-control');
    $form['search_block_form']['#size'] = FALSE;
    $form['#attributes']['class'] = array('form-inline');
  }
} 

/**
 * Preprocesses variables for page.tpl.php.
 */
function katturai_preprocess_html(&$variables) {
  _katturai_load_bootstrap();
}

/**
 * Loads Twitter Bootstrap library.
 */
function _katturai_load_bootstrap() {
  $version = check_plain(theme_get_setting('bootstrap_version'));
  $js_path = '/js/bootstrap.min.js';
  $js_options = array(
    'group' => JS_LIBRARY,
  );
  $css_path = '/css/bootstrap.min.css';
  $cssr_path = '/css/bootstrap-responsive.min.css';
  $css_options = array(
    'group' => CSS_THEME,
    'weight' => -1000,
    'every_page' => TRUE,
  );
  switch (theme_get_setting('bootstrap_source')) {
    case 'bootstrapcdn':
      $bootstrap_path = '//netdna.bootstrapcdn.com/bootstrap/' . $version;
      $js_options['type'] = 'external';
      $css_path = '/css/bootstrap.min.css';
      unset($cssr_path);
      $css_options['type'] = 'external';
      break;

    case 'libraries':
      if (module_exists('libraries')) {
        $bootstrap_path = libraries_get_path('bootstrap');
      }
      break;

    case 'theme';
      $bootstrap_path = path_to_theme() . '/libraries/bootstrap';
      break;

    default:
      return;
  }
  _katturai_add_asset('js', $bootstrap_path . $js_path, $js_options);
  _katturai_add_asset('css', $bootstrap_path . $css_path, $css_options);
  if (isset($cssr_path)) {
    _katturai_add_asset('css', $bootstrap_path . $cssr_path, $css_options);
  }
}

/**
 * Adds js/css file.
 */
function _katturai_add_asset($type, $data, $options) {
  if (isset($options['browsers']) && !is_array($options['browsers'])) {
    $options['browsers'] = _katturai_browsers_to_array($options['browsers']);
  }
  switch ($type) {
    case 'css':
      drupal_add_css($data, $options);
      break;

    case 'js':
      if (isset($options['browsers'])) {
        $data = file_create_url($data);
        $elements = array(
          '#markup' => '<script type="text/javascript" src="' . $data . '"></script>',
          '#browsers' => $options['browsers'],
        );
        $elements = drupal_pre_render_conditional_comments($elements);
        _katturai_add_html_head_bottom(drupal_render($elements));
      }
      else {
        drupal_add_js($data, $options);
      }
      break;
  }
}

/**
 * Converts string representation of browsers to the array.
 */
function _katturai_browsers_to_array($browsers) {
  switch ($browsers) {
    case 'modern':
      return array('IE' => 'gte IE 9', '!IE' => TRUE);

    case 'obsolete':
      return array('IE' => 'lt IE 9', '!IE' => FALSE);
  }
  return array('IE' => TRUE, '!IE' => TRUE);
}

/**
 * Allows to add an extra html markup to the bottom of <head>.
 */
function _katturai_add_html_head_bottom($data = NULL) {
  $head_bottom = &drupal_static(__FUNCTION__);
  if (!isset($head_bottom)) {
    $head_bottom = '';
  }

  if (isset($data)) {
    $head_bottom .= $data;
  }
  return $head_bottom;
}

/**
 * Implements hook_page_alter().
 */
function katturai_page_alter($page) {
  $viewport = array(
    '#type' => 'html_tag',
    '#tag' => 'meta',
    '#attributes' => array(
      'name' => 'viewport',
      'content' => 'width=device-width, initial-scale=1, maximum-scale=1',
    ),
  );
  drupal_add_html_head($viewport, 'viewport');
}

/**
 * Implements THEMENAME_form_comment_form_alter().
 */
function katturai_form_comment_form_alter(&$form, &$form_state) {
  $form['author']['name']['#attributes']['class'] = array('form-control');
  $form['subject']['#attributes']['class'] = array('form-control');
  $form['comment_body']['und'][0]['#attributes']['class'] = array('form-control');
  $form['actions']['submit']['#attributes']['class'] = array('btn btn-default');
  $form['actions']['preview']['#attributes']['class'] = array('btn btn-default');
}

/**
 * Implements THEMENAME_links__system_main_menu().
 */
function katturai_links__system_main_menu($variables) {
  $links = $variables['links'];
  $attributes = $variables['attributes'];
  $heading = $variables['heading'];
  global $language_url;
  $output = '';

  if (count($links) > 0) {
    $output = '';

    // Treat the heading first if it is present to prepend it to the
    // list of links.
    if (!empty($heading)) {
      if (is_string($heading)) {
        // Prepare the array that will be used when the passed heading
        // is a string.
        $heading = array(
          'text' => $heading,

          // Set the default level of the heading.
          'level' => 'h2',
        );
      }
      $output .= '<' . $heading['level'];
      if (!empty($heading['class'])) {
        $output .= drupal_attributes(array('class' => $heading['class']));
      }
      $output .= '>' . check_plain($heading['text']) . '</' . $heading['level'] . '>';
    }

    $output .= '<ul' . drupal_attributes($attributes) . '>';

    $num_links = count($links);
    $i = 1;

    foreach ($links as $key => $link) {
      $class = array($key);

      // Add first, last and active classes to the list of links to help out
      // themers.
      if ($i == 1) {
        $class[] = 'first';
      }
      if ($i == $num_links) {
        $class[] = 'last';
      }
      if (isset($link['href']) && ($link['href'] == $_GET['q'] || ($link['href'] == '<front>' && drupal_is_front_page())) && (empty($link['language']) || $link['language']->language == $language_url->language)) {
        $class[] = 'active';
      }
      $output .= '<li' . drupal_attributes(array('class' => $class)) . '>';

      if (isset($link['href'])) {
        // Pass in $link as $options, they share the same keys.
        $output .= l($link['title'], $link['href'], $link);
      }
      elseif (!empty($link['title'])) {
        // Some links are actually not links, but we wrap these in <span>
        // for adding title and class attributes.
        if (empty($link['html'])) {
          $link['title'] = check_plain($link['title']);
        }
        $span_attributes = '';
        if (isset($link['attributes'])) {
          $span_attributes = drupal_attributes($link['attributes']);
        }
        $output .= '<span' . $span_attributes . '>' . $link['title'] . '</span>';
      }

      $i++;
      $output .= "<span class='menu-divider'>/</span></li>\n";
    }

    $output .= '</ul>';
  }

  return $output;
}
