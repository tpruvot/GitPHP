/*
 * GitPHP javascript login popup
 *
 * @author Christopher Han <xiphux@gmail.com>
 * @copyright Copyright (c) 2012 Christopher Han
 * @package GitPHP
 * @subpackage Javascript
 */

define(['jquery', 'modules/geturl', 'modules/resources', 'qtip'], function($, url, resources) {
  return function(element) {
    $(element).qtip({
      content: {
        text: function(api) {
          var container = $(document.createElement('div'));
          var loginError = $(document.createElement('div')).addClass('loginError').addClass('error').css('padding-top', '0px');
          var loginDiv = $(document.createElement('div')).addClass('loginForm');
          var loginForm = $(document.createElement('form'))

          container.append(loginDiv);
          loginDiv.append(loginForm);
          loginForm.append(loginError);

          var usernameDiv = $(document.createElement('div')).addClass('field');
          var usernameLabel = $(document.createElement('label')).attr('for', 'username').text(resources.UsernameLabel);
          var usernameField = $(document.createElement('input')).attr('type', 'text').attr('name', 'username').attr('id', 'username');
          usernameDiv.append(usernameLabel);
          usernameDiv.append(usernameField);
          loginForm.append(usernameDiv);

          var passwordDiv = $(document.createElement('div')).addClass('field');
          var passwordLabel = $(document.createElement('label')).attr('for', 'password').text(resources.PasswordLabel);
          var passwordField = $(document.createElement('input')).attr('type', 'password').attr('name', 'password').attr('id', 'password');
          passwordDiv.append(passwordLabel);
          passwordDiv.append(passwordField);
          loginForm.append(passwordDiv);

          var loginDiv = $(document.createElement('div')).addClass('submit');
          var loginButton = $(document.createElement('input')).attr('type', 'submit').attr('value', resources.Login);
          loginDiv.append(loginButton);
          loginForm.append(loginDiv);

          loginForm.bind('submit', function(event) {
            var username = $('input[name=username]', this).val();
            var password = $('input[name=password]', this).val();
            var errorContainer = $('.loginError', this);
            if (!username) {
              errorContainer.text(resources.UsernameIsRequired);
              return false;
            }
            if (!password) {
              errorContainer.text(resources.PasswordIsRequired);
              return false;
            }
            errorContainer.text('');
            var inputs = $('input', this);
            $.ajax({
              url: url + '?a=login&o=js',
              data: {
                a: 'login',
                o: 'js',
                username: username,
                password: password
              },
              type: 'post',
              dataType: 'json',
              success: function(data, status, jqXHR) {
                if (data) {
                  if (data.success === true) {
                    window.location.reload();
                  } else if (data.message) {
                    errorContainer.text(data.message);
                  } else {
                    errorContainer.text(resources.AnErrorOccurredWhileLoggingIn);
                  }
                } else {
                  errorContainer.text(resources.AnErrorOccurredWhileLoggingIn);
                }
              },
              error: function(jqXHR, message) {
                  errorContainer.text(resources.AnErrorOccurredWhileLoggingIn);
              },
              beforeSend: function() {
                inputs.attr('disabled', 'disabled');
              },
              complete: function() {
                inputs.removeAttr('disabled');
              }
            });

            return false;
          });

          return container;
        },
        title: {
          text: resources.LoginTitle,
          button: true
        }
      },
      position: {
        my: 'center',
        at: 'center',
        target: $(window)
      },
      show: {
        event: 'click',
        modal: {
          on: true
        }
      },
      hide: {
        event: false
      },
      style: {
        classes: 'ui-tooltip-light ui-tooltip-shadow'
      },
      events: {
        visible: function(event, api) {
          $('input[name=username]', this).focus();
        }
      }
    });
    $(element).click(function(event) {
      return false;
    });
  };
});
