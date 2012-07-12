<?php

function setup_user() {
  global $grid;
  $sessions_dir = GRID_DIR . '/data/sessions';
  if (!is_writable($sessions_dir)) {
    $grid->user = (object) array(
      'id' => 0,
      'name' => 'Anonymous'
    );
    return;
  }
  ini_set('session.name', 'SESSION');
  ini_set('session.save_handler', 'files');
  ini_set('session.save_path', $sessions_dir);
  ini_set('session.use_cookies', true);
  ini_set('session.cookie_lifetime', time() + 60 * 60 * 24 * 3);
  ini_set('session.gc_maxlifetime', time() + 60 * 60 * 24 * 3);
  session_start();
  $now = time();
  if (empty($_SESSION['user_id'])) {
    $user_id = uniqid('user.', true);
    $grid->db->insert('user', array(
      'id' => $user_id,
      'server_id' => $grid->meta['server_id'],
      'created' => $now,
      'updated' => $now
    ));
    $_SESSION['user_id'] = $user_id;
  } else {
    $user_id = $_SESSION['user_id'];
  }
  $grid->user = $grid->db->record('user', $user_id);
  if (empty($grid->user)) {
    $grid->db->insert('user', array(
      'id' => $user_id,
      'server_id' => $grid->meta['server_id'],
      'created' => $now,
      'updated' => $now
    ));
    $grid->user = $grid->db->record('user', $user_id);
  }
  $grid->users = array(
    $user_id => $grid->user
  );
}

function setup_meta() {
  global $grid;
  $grid->meta = array();
  $result = $grid->db->select('meta');
  foreach ($result as $record) {
    $grid->meta[$record->name] = $record->value;
  }
  if (empty($grid->meta['server_id'])) {
    save_meta(array(
      'server_id' => uniqid('server.', true),
      'last_updated' => '{}'
    ));
  }
}

function save_meta($meta) {
  global $grid;
  foreach ($meta as $key => $value) {
    if (isset($grid->meta[$key])) {
      $values = array(
        'value' => $value
      );
      $grid->db->update('meta', $values, 'name = ?', array($key));
    } else {
      $values = array(
        'name' => $key,
        'value' => $value
      );
      $grid->db->insert('meta', $values);
    }
    $grid->meta[$key] = $value;
  }
}

function get_user($target) {
  global $grid;
  if (is_string($target)) {
    if (isset($grid->users[$target])) {
      return $grid->users[$target];
    } else {
      $target = $grid->db->record('user', $target);
      if (!empty($target)) {
        return $grid->users[$target->id];
      }
    }
  }
  return $target;
}

function get_username($target) {
  $user = get_user($target);
  if (empty($user->name)) {
    return 'Anonymous';
  } else {
    return $user->name;
  }
}

function get_bio($target) {
  $user = get_user($target);
  if (empty($user->bio)) {
    return '';
  } else {
    return $user->bio;
  }
}

function elapsed_time($time) {
  $time = time() - $time;
  $tokens = array (
    31536000 => 'year',
    2592000 => 'month',
    604800 => 'week',
    86400 => 'day',
    3600 => 'hour',
    60 => 'minute',
    1 => 'second'
  );
  foreach ($tokens as $unit => $text) {
    if ($time < $unit) {
      continue;
    }
    $number = floor($time / $unit);
    $s = ($number == 1) ? '' : 's';
    return "$number $text$s ago";
  }
  return "moments ago";
}

function admin_password_set() {
  global $grid;
  $password = $grid->db->record('meta', 'name = ?', 'admin_password');
  return !empty($password);
}

function wispr_ping() {
  global $grid;
  $now = time();
  $record = $grid->db->record('wispr', $_SERVER['REMOTE_ADDR']);
  if (empty($record)) {
    $grid->db->insert('wispr', array(
      'id' => $_SERVER['REMOTE_ADDR'],
      'status' => 'show-intro',
      'created' => $now
    ));
  }
}

function wispr_pong() {
  global $grid;
  $record = $grid->db->record('wispr', $_SERVER['REMOTE_ADDR']);
  if (empty($record)) {
    return false;
  } else {
    return $record->status;
  }
}

?>
