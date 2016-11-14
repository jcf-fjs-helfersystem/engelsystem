<?php

function login_title() {
  return _("Login");
}

function register_title() {
  return _("Register");
}

function logout_title() {
  return _("Logout");
}

// Engel registrieren
function guest_register() {
  global $tshirt_sizes, $enable_tshirt_size, $enable_dect, $enable_jabber, $enable_emailcheckbox, $enable_planned_arrival_date, $enable_First_Last_Name, $enable_Hometown, $enable_description_jobs, $enable_age, $enable_phone, $enable_angeltypedescription, $default_theme, $user;

  $event_config = EventConfig();
  if ($event_config === false) {
    engelsystem_error("Unable to load event config.");
  }

  $msg = "";
  $nick = "";
  $lastname = "";
  $prename = "";
  $age = "";
  $tel = "";
  $dect = "";
  $mobile = "";
  $mail = "";
  $email_shiftinfo = false;
  $jabber = "";
  $hometown = "";
  $comment = "";
  $tshirt_size = '';
  $password_hash = "";
  $selected_angel_types = [];
  $planned_arrival_date = null;

  $angel_types_source = sql_select("SELECT * FROM `AngelTypes` ORDER BY `name`");
  $angel_types = [];
  foreach ($angel_types_source as $angel_type) {
    $angel_types[$angel_type['id']] = $angel_type['name'] . ($angel_type['restricted'] ? " (restricted)" : "");
    if (! $angel_type['restricted']) {
      $selected_angel_types[] = $angel_type['id'];
    }
  }

  if (isset($_REQUEST['submit'])) {
    $valid = true;

    if (isset($_REQUEST['nick']) && strlen(User_validate_Nick($_REQUEST['nick'])) > 1) {
      $nick = User_validate_Nick($_REQUEST['nick']);
      if (sql_num_query("SELECT * FROM `User` WHERE `Nick`='" . sql_escape($nick) . "' LIMIT 1") > 0) {
        $valid = false;
        $msg .= error(sprintf(_("Your nick &quot;%s&quot; already exists."), $nick), true);
      }
    } else {
      $valid = false;
      $msg .= error(sprintf(_("Your nick &quot;%s&quot; is too short (min. 2 characters)."), User_validate_Nick($_REQUEST['nick'])), true);
    }

    if (isset($_REQUEST['mail']) && strlen(strip_request_item('mail')) > 0) {
      $mail = strip_request_item('mail');
      if (! check_email($mail)) {
        $valid = false;
        $msg .= error(_("E-mail address is not correct."), true);
      }
    } else {
      $valid = false;
      $msg .= error(_("Please enter your e-mail."), true);
    }

    if (isset($_REQUEST['email_shiftinfo'])) {
      $email_shiftinfo = true;
    }

    if (isset($_REQUEST['jabber']) && strlen(strip_request_item('jabber')) > 0) {
      $jabber = strip_request_item('jabber');
      if (! check_email($jabber)) {
        $valid = false;
        $msg .= error(_("Please check your jabber account information."), true);
      }
    }

    if ($enable_tshirt_size) {
      if (isset($_REQUEST['tshirt_size']) && isset($tshirt_sizes[$_REQUEST['tshirt_size']]) && $_REQUEST['tshirt_size'] != '') {
        $tshirt_size = $_REQUEST['tshirt_size'];
      } else {
        $valid = false;
        $msg .= error(_("Please select your shirt size."), true);
      }
    }

    if (isset($_REQUEST['password']) && strlen($_REQUEST['password']) >= MIN_PASSWORD_LENGTH) {
      if ($_REQUEST['password'] != $_REQUEST['password2']) {
        $valid = false;
        $msg .= error(_("Your passwords don't match."), true);
      }
    } else {
      $valid = false;
      $msg .= error(sprintf(_("Your password is too short (please use at least %s characters)."), MIN_PASSWORD_LENGTH), true);
    }
    if ($enable_planned_arrival_date) {
    if (isset($_REQUEST['planned_arrival_date']) && DateTime::createFromFormat("Y-m-d", trim($_REQUEST['planned_arrival_date']))) {
      $planned_arrival_date = DateTime::createFromFormat("Y-m-d", trim($_REQUEST['planned_arrival_date']))->getTimestamp();
    } else {
      $valid = false;
      $msg .= error(_("Please enter your planned date of arrival."), true);
    }
    }
    $selected_angel_types = [];
    foreach (array_keys($angel_types) as $angel_type_id) {
      if (isset($_REQUEST['angel_types_' . $angel_type_id])) {
        $selected_angel_types[] = $angel_type_id;
      }
    }

    // Trivia
    if (isset($_REQUEST['lastname'])) {
      $lastname = strip_request_item('lastname');
    }
    if (isset($_REQUEST['prename'])) {
      $prename = strip_request_item('prename');
    }
    if (isset($_REQUEST['age']) && preg_match("/^[0-9]{0,4}$/", $_REQUEST['age'])) {
      $age = strip_request_item('age');
    }
    if (isset($_REQUEST['tel'])) {
      $tel = strip_request_item('tel');
    }
    if (isset($_REQUEST['dect'])) {
      $dect = strip_request_item('dect');
    }
    if (isset($_REQUEST['mobile'])) {
      $mobile = strip_request_item('mobile');
    }
    if (isset($_REQUEST['hometown'])) {
      $hometown = strip_request_item('hometown');
    }
    if (isset($_REQUEST['comment'])) {
      $comment = strip_request_item_nl('comment');
    }

    if ($valid) {
      sql_query("
          INSERT INTO `User` SET
          `color`='" . sql_escape($default_theme) . "',
          `Nick`='" . sql_escape($nick) . "',
          `Vorname`='" . sql_escape($prename) . "',
          `Name`='" . sql_escape($lastname) . "',
          `Alter`='" . sql_escape($age) . "',
          `Telefon`='" . sql_escape($tel) . "',
          `DECT`='" . sql_escape($dect) . "',
          `Handy`='" . sql_escape($mobile) . "',
          `email`='" . sql_escape($mail) . "',
          `email_shiftinfo`=" . sql_bool($email_shiftinfo) . ",
          `jabber`='" . sql_escape($jabber) . "',
          `Size`='" . sql_escape($tshirt_size) . "',
          `Passwort`='" . sql_escape($password_hash) . "',
          `kommentar`='" . sql_escape($comment) . "',
          `Hometown`='" . sql_escape($hometown) . "',
          `CreateDate`=NOW(),
          `Sprache`='" . sql_escape($_SESSION["locale"]) . "',
          `arrival_date`=NULL,
          `planned_arrival_date`='" . sql_escape($planned_arrival_date) . "'");

      // Assign user-group and set password
      $user_id = sql_id();
      sql_query("INSERT INTO `UserGroups` SET `uid`='" . sql_escape($user_id) . "', `group_id`=-2");
      set_password($user_id, $_REQUEST['password']);

      // Assign angel-types
      $user_angel_types_info = [];
      foreach ($selected_angel_types as $selected_angel_type_id) {
        sql_query("INSERT INTO `UserAngelTypes` SET `user_id`='" . sql_escape($user_id) . "', `angeltype_id`='" . sql_escape($selected_angel_type_id) . "'");
        $user_angel_types_info[] = $angel_types[$selected_angel_type_id];
      }

      engelsystem_log("User " . User_Nick_render(User($user_id)) . " signed up as: " . join(", ", $user_angel_types_info));
      success(_("Helper registration successful!"));

      // User is already logged in - that means a coordinator has registered an angel. Return to register page.
      if (isset($user)) {
        redirect(page_link_to('register'));
      }

      // If a welcome message is present, display registration success page.
      if ($event_config != null && $event_config['event_welcome_msg'] != null) {
        return User_registration_success_view($event_config['event_welcome_msg']);
      }

      redirect('?');
    }
  }

  return page_with_title(register_title(), [
      _("By completing this form you're registering as a JCF-Helper. This script will create you an account in the helper task scheduler."),
      $msg,
      msg(),
      form([
          div('row', [
              div('col-md-6', [
                  div('row', [
                      div('col-sm-4', [
                          form_text('nick', _("Login-Name") . ' ' . entry_required(), $nick)
                      ]),
                      div('col-sm-8', [
                          form_email('mail', _("E-Mail") . ' ' . entry_required(), $mail),
                          $enable_emailcheckbox ? form_checkbox('email_shiftinfo', _("Please send me an email if my shifts change"), $email_shiftinfo) : ''
                      ])
                  ]),
                  div('row', [
                      div('col-sm-6', [
                          $enable_planned_arrival_date ? form_date('planned_arrival_date', _("Planned date of arrival") . ' ' . entry_required(), $planned_arrival_date, time()) : ''
                      ]),
                      div('col-sm-6', [
                          $enable_tshirt_size ? form_select('tshirt_size', _("Shirt size") . ' ' . entry_required(), $tshirt_sizes, $tshirt_size) : ''
                      ])
                  ]),
                  div('row', [
                      div('col-sm-6', [
                          form_password('password', _("Password") . ' ' . entry_required())
                      ]),
                      div('col-sm-6', [
                          form_password('password2', _("Confirm password") . ' ' . entry_required())
                      ])
                  ]),
                  form_checkboxes('angel_types', _($enable_description_jobs ? "What do you want to do?" : 'When can you help?') . sprintf($enable_description_jobs ? " (<a href=\"%s\">%s</a>)" : '', page_link_to('angeltypes') . '&action=about', _($enable_description_jobs ? "Description of job types" : '')), $angel_types, $selected_angel_types),
                  form_info($enable_description_jobs ? "" : '', _($enable_description_jobs ? "Restricted helper types need will be confirmed later by the Guest Service Team. You can change your selection in the options section." : 'Ist dein Gesundheitszeugnis aktuell gültig?'))
              ]),
              div('col-md-6', [
                  div('row', [
                      div('col-sm-4', [
                        form_text('mobile', _("Cellphone (for Whatsapp-Helper-Group)"), $mobile)
                      ]),
                      div('col-sm-4', [
                        $enable_phone ? form_text('tel', _("Phone"), $tel) : ''
                      ]),
                      div('col-sm-4', [
                          $enable_dect ? form_text('dect', _("DECT"), $dect) : ''
                      ])
                  ]),
                  $enable_jabber ? form_text('jabber', _("Jabber"), $jabber) : '' ,
                  div('row', [
                      div('col-sm-6', [
                          $enable_First_Last_Name ? form_text('prename', _("First name"), $prename) : ''
                      ]),
                      div('col-sm-6', [
                          $enable_First_Last_Name ? form_text('lastname', _("Last name"), $lastname) : ''
                      ])
                  ]),
                  div('row', [
                      div('col-sm-3', [
                          $enable_age ? form_text('age', _("Age"), $age) : ''
                      ]),
                      div('col-sm-9', [
                          $enable_Hometown ? form_text('hometown', _("Hometown"), $hometown) : ''
                      ])
                  ]),
                  form_info(entry_required() . ' = ' . _("Entry required!"))
              ])
          ]),
          // form_textarea('comment', _("Did you help at former CCC events and which tasks have you performed then?"), $comment),
          form_submit('submit', _("Register"))
      ])
  ]);
}

function entry_required() {
  return '<span class="text-info glyphicon glyphicon-warning-sign"></span>';
}

function guest_logout() {
  session_destroy();
  redirect(page_link_to("start"));
}

function guest_login() {
  $nick = "";

  unset($_SESSION['uid']);

  if (isset($_REQUEST['submit'])) {
    $valid = true;

    if (isset($_REQUEST['nick']) && strlen(User_validate_Nick($_REQUEST['nick'])) > 0) {
      $nick = User_validate_Nick($_REQUEST['nick']);
      $login_user = sql_select("SELECT * FROM `User` WHERE `Nick`='" . sql_escape($nick) . "'");
      if (count($login_user) > 0) {
        $login_user = $login_user[0];
        if (isset($_REQUEST['password'])) {
          if (! verify_password($_REQUEST['password'], $login_user['Passwort'], $login_user['UID'])) {
            $valid = false;
            error(_("Your password is incorrect.  Please try it again."));
          }
        } else {
          $valid = false;
          error(_("Please enter a password."));
        }
      } else {
        $valid = false;
        error(_("No user was found with that Nickname. Please try again. If you are still having problems, ask an Dispatcher."));
      }
    } else {
      $valid = false;
      error(_("Please enter a nickname."));
    }

    if ($valid) {
      $_SESSION['uid'] = $login_user['UID'];
      $_SESSION['locale'] = $login_user['Sprache'];

      redirect(page_link_to('news'));
    }
  }

  $event_config = EventConfig();
  if ($event_config === false) {
    engelsystem_error("Unable to load event config.");
  }

  return page([
      div('col-md-12', [
          div('row', [
              div('col-md-4', [
					EventConfig_countdown_page($event_config)
              ]),
              div('col-md-4', [
                  heading(login_title(), 2),
                  msg(),
                  form([
                      form_text('nick', _("Login-Name"), $nick),
                      form_password('password', _("Password")),
                      form_submit('submit', _("Login")),
                      buttons([
                          button(page_link_to('user_password_recovery'), _("I forgot my password"))
                      ]),
                      info(_("Please note: You have to activate cookies!"), true)
                  ])
              ]),
              div('col-md-4', [
                  heading(register_title(), 2),
                  get_register_hint(),
				  // !!! WURDE MANUELL ENTFERNT !!
                  //heading(_("What can I do?"), 2),
                  //'<p>' . _("Please read about the jobs you can do to help us.") . '</p>',
                  //buttons([
                  //    button(page_link_to('angeltypes') . '&action=about', _("Teams/Job description") . ' &raquo;')
                  //])
              ])
          ])
      ])
  ]);
}

function get_register_hint() {
  global $privileges;

  if (in_array('register', $privileges)) {
    return join('', [
        '<p>' . _("Please sign up, if you want to help us!") . '</p>',
        buttons([
            button(page_link_to('register'), register_title() . ' &raquo;')
        ])
    ]);
  }

  return error(_("Registration is disabled."), true);
}
?>
